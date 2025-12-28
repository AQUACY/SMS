<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Subscription;
use App\Services\MomoPaymentService;
use App\Services\DirectMomoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends BaseApiController
{
    /**
     * Display a listing of payments
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        $query = Payment::whereHas('student', function ($q) use ($request) {
            $q->where('school_id', $request->get('school_id'));
        })->with(['parent.user', 'student', 'term', 'initiatedBy']);

        // Filter by payment type based on user role
        if ($user->isSuperAdmin()) {
            // Super admin sees subscription payments (revenue for platform)
            $query->where('payment_type', 'subscription_payment');
        } elseif ($user->isSchoolAdmin() || $user->isAccountsManager()) {
            // School admin/accounts manager sees fee payments (revenue for school)
            $query->where('payment_type', 'fee_payment');
        }

        // Allow manual override via query parameter
        if ($request->has('payment_type')) {
            $query->where('payment_type', $request->get('payment_type'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
        }

        if ($request->has('term_id')) {
            $query->where('term_id', $request->get('term_id'));
        }

        // Search functionality
        if ($request->has('search') && $request->get('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('parent.user', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                  })
                  ->orWhereHas('student', function ($q) use ($search) {
                      $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('student_number', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginated($payments, 'Payments retrieved successfully');
    }

    /**
     * Display the specified payment
     */
    public function show(Request $request, Payment $payment): JsonResponse
    {
        if ($payment->student->school_id !== $request->get('school_id')) {
            return $this->error('Payment not found', 404);
        }

        $payment->load(['parent.user', 'student', 'term', 'subscription']);

        return $this->success($payment, 'Payment retrieved successfully');
    }

    /**
     * Create a new payment (initiate payment)
     */
    public function store(Request $request): JsonResponse
    {
        $paymentType = $request->get('payment_type');
        
        // Validation rules - different for fee vs subscription payments
        $rules = [
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'payment_type' => ['required', 'in:fee_payment,subscription_payment'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:momo,bank,cash,other'],
        ];
        
        // For subscription payments with MoMo, require provider and number
        // For fee payments, MoMo is optional (manual processing)
        if ($paymentType === 'subscription_payment') {
            $rules['momo_provider'] = ['required_if:payment_method,momo', 'in:mtn,vodafone,airteltigo'];
            $rules['momo_number'] = ['required_if:payment_method,momo', 'string', 'max:20'];
        } else {
            // Fee payments - MoMo fields are optional
            $rules['momo_provider'] = ['nullable', 'in:mtn,vodafone,airteltigo'];
            $rules['momo_number'] = ['nullable', 'string', 'max:20'];
        }
        
        $request->validate($rules);

        $user = auth()->user();
        $studentId = $request->get('student_id');
        $termId = $request->get('term_id');

        // For subscription payments, require parent
        if ($paymentType === 'subscription_payment') {
            $parent = $user->parent;
            if (!$parent) {
                return $this->error('Parent profile not found', 404);
            }
            $parentId = $parent->id;
            $initiatedBy = null;
        } else {
            // For fee payments, can be initiated by accounts manager or parent
            if ($user->isAccountsManager() || $user->isSchoolAdmin()) {
                // Admin/Accounts Manager initiating for a parent
                $request->validate([
                    'parent_id' => ['required', 'exists:parents,id'],
                ]);
                $parentId = $request->get('parent_id');
                $initiatedBy = $user->id;
            } else {
                // Parent initiating their own fee payment
                $parent = $user->parent;
                if (!$parent) {
                    return $this->error('Parent profile not found', 404);
                }
                $parentId = $parent->id;
                $initiatedBy = null;
            }
        }

        // For subscription payments, check if subscription already exists
        if ($paymentType === 'subscription_payment') {
            $existingSubscription = Subscription::where('parent_id', $parentId)
                ->where('student_id', $studentId)
                ->where('term_id', $termId)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                return $this->error('Active subscription already exists for this student and term', 422);
            }
        }
        
        // For fee payments, check if a completed payment already exists
        if ($paymentType === 'fee_payment') {
            $existingCompletedPayment = Payment::where('parent_id', $parentId)
                ->where('student_id', $studentId)
                ->where('term_id', $termId)
                ->where('payment_type', 'fee_payment')
                ->where('status', 'completed')
                ->first();

            if ($existingCompletedPayment) {
                return $this->error(
                    'Fee payment for this student and term has already been completed and verified.',
                    422,
                    [
                        'payment_id' => $existingCompletedPayment->id,
                        'payment_reference' => $existingCompletedPayment->reference,
                        'verified_at' => $existingCompletedPayment->verified_at,
                    ]
                );
            }
        }

        // CRITICAL: Check for duplicate pending/processing payments
        // This prevents multiple payments for the same service
        $existingPendingPayment = Payment::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('payment_type', $paymentType)
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '>', now()->subMinutes(30)) // Within last 30 minutes
            ->first();

        if ($existingPendingPayment) {
            return $this->error(
                'A payment is already in progress for this student and term. Please wait for it to complete or check your payment status.',
                422,
                [
                    'payment_id' => $existingPendingPayment->id,
                    'payment_reference' => $existingPendingPayment->reference,
                    'payment_status' => $existingPendingPayment->status,
                ]
            );
        }

        // Check for recently completed payment (within last 5 minutes) to prevent accidental duplicates
        $recentCompletedPayment = Payment::where('parent_id', $parentId)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('payment_type', $paymentType)
            ->where('status', 'completed')
            ->where('created_at', '>', now()->subMinutes(5))
            ->first();

        if ($recentCompletedPayment) {
            return $this->error(
                'A payment was recently completed for this student and term. Please check your subscriptions.',
                422,
                [
                    'payment_id' => $recentCompletedPayment->id,
                    'payment_reference' => $recentCompletedPayment->reference,
                ]
            );
        }

        // Generate unique reference
        $reference = 'PAY-' . Str::upper(Str::random(10));
        
        // Ensure reference is unique (very rare collision, but safety first)
        while (Payment::where('reference', $reference)->exists()) {
            $reference = 'PAY-' . Str::upper(Str::random(10));
        }

        // Generate unique verification token for parent to use in payment reference
        // Format: TOKEN-XXXX-XXXX (e.g., TOKEN-A1B2-C3D4)
        $verificationToken = 'TOKEN-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        while (Payment::where('verification_token', $verificationToken)->exists()) {
            $verificationToken = 'TOKEN-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        }

        // Create payment with 'processing' status initially to prevent race conditions
        $payment = Payment::create([
            'parent_id' => $parentId,
            'student_id' => $studentId,
            'term_id' => $termId,
            'payment_type' => $paymentType,
            'initiated_by' => $initiatedBy,
            'amount' => $request->get('amount'),
            'currency' => 'GHS',
            'payment_method' => $request->get('payment_method'),
            'momo_provider' => $request->get('momo_provider'),
            'momo_number' => $request->get('momo_number'),
            'reference' => $reference,
            'verification_token' => $verificationToken,
            'status' => 'processing', // Set to processing - initiating payment
        ]);

        // Fee payments are always manual - no API integration
        // Only subscription payments use payment gateway API
        if ($paymentType === 'fee_payment') {
            // For fee payments, create pending payment record
            // Admin/Accounts Manager will manually verify after parent pays at school
            $payment->status = 'pending';
            $payment->save();

            $payment->load(['student', 'term', 'initiatedBy']);

            return $this->success([
                'payment' => $payment,
                'message' => 'Fee payment record created. Please pay at the school accounts office. The accounts manager will verify your payment.',
            ], 'Fee payment record created. Please proceed to the school accounts office to complete payment.', 201);
        }

        // For subscription payments, use direct mobile money service
        if ($request->get('payment_method') === 'momo') {
            try {
                $directMomoService = new DirectMomoService();
                
                $paymentResult = $directMomoService->initiatePayment([
                    'amount' => $request->get('amount'),
                    'phone' => $request->get('momo_number'),
                    'provider' => $request->get('momo_provider'),
                    'reference' => $reference,
                ]);

                if ($paymentResult['success']) {
                    // Payment request created successfully
                    $payment->status = 'pending';
                    $payment->momo_transaction_id = $paymentResult['transaction_id'] ?? $reference;
                    $payment->webhook_data = [
                        'provider_response' => $paymentResult,
                        'instructions' => $paymentResult['instructions'] ?? null,
                    ];
                    $payment->save();

                    // Start automatic verification polling in background
                    $this->startPaymentVerification($payment->id, $request->get('momo_provider'));

                    $payment->load(['student', 'term', 'initiatedBy']);

                    return $this->success([
                        'payment' => $payment,
                        'message' => $paymentResult['message'] ?? 'Payment request created. Please complete payment on your mobile device.',
                        'transaction_id' => $paymentResult['transaction_id'],
                        'instructions' => $paymentResult['instructions'] ?? null,
                    ], 'Payment initiated successfully. Please complete payment on your mobile device.', 201);
                } else {
                    // Payment initiation failed
                    $payment->status = 'failed';
                    $payment->webhook_data = $paymentResult;
                    $payment->save();

                    return $this->error($paymentResult['message'] ?? 'Failed to initiate payment. Please try again.', 500);
                }
            } catch (\Exception $e) {
                \Log::error('Direct MoMo Payment Initiation Error', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                $payment->status = 'failed';
                $payment->save();

                return $this->error('Failed to initiate payment. Please try again later.', 500);
            }
        } else {
            // For non-MoMo payments (bank, cash, other), keep manual verification flow
            $payment->status = 'pending';
            $payment->save();

            $payment->load(['student', 'term', 'initiatedBy']);

            $message = $paymentType === 'subscription_payment' 
                ? 'Subscription payment token generated successfully. Use this token in your payment reference.'
                : 'School fee payment token generated successfully. Use this token in your payment reference.';

            $instructions = $paymentType === 'subscription_payment'
                ? 'Use the verification token in your payment reference. After making payment, verify it using the token to activate your subscription immediately.'
                : 'Use the verification token in your payment reference. After making payment, verify it using the token to complete the fee payment.';

            return $this->success([
                'payment' => $payment,
                'verification_token' => $verificationToken,
                'instructions' => $instructions,
            ], $message, 201);
        }
    }

    /**
     * Manually initiate fee payment (for accounts manager/admin)
     */
    public function initiateFeePayment(Request $request): JsonResponse
    {
        $request->validate([
            'parent_id' => ['required', 'exists:parents,id'],
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:momo,bank,cash,other'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $user = auth()->user();
        
        if (!$user->isAccountsManager() && !$user->isSchoolAdmin()) {
            return $this->error('Only accounts managers and school admins can initiate fee payments', 403);
        }

        $parentId = $request->get('parent_id');
        $studentId = $request->get('student_id');
        $termId = $request->get('term_id');

        // Generate unique reference
        $reference = 'FEE-' . Str::upper(Str::random(10));
        while (Payment::where('reference', $reference)->exists()) {
            $reference = 'FEE-' . Str::upper(Str::random(10));
        }

        // Generate verification token
        $verificationToken = 'TOKEN-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        while (Payment::where('verification_token', $verificationToken)->exists()) {
            $verificationToken = 'TOKEN-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
        }

        $payment = Payment::create([
            'parent_id' => $parentId,
            'student_id' => $studentId,
            'term_id' => $termId,
            'payment_type' => 'fee_payment',
            'initiated_by' => $user->id,
            'amount' => $request->get('amount'),
            'currency' => 'GHS',
            'payment_method' => $request->get('payment_method'),
            'reference' => $reference,
            'verification_token' => $verificationToken,
            'payment_reference' => $request->get('payment_reference'),
            'status' => $request->get('payment_reference') ? 'pending' : 'pending', // Can be verified immediately if reference provided
        ]);

        // If payment reference is provided, mark as completed immediately
        if ($request->get('payment_reference')) {
            $payment->markAsCompleted('accounts_manager');
        }

        $payment->load(['student', 'term', 'parent.user', 'initiatedBy']);

        return $this->success($payment, 'Fee payment initiated successfully', 201);
    }

    /**
     * Verify payment (webhook or manual)
     */
    public function verify(Request $request, Payment $payment): JsonResponse
    {
        $request->validate([
            'verified_by' => ['required', 'in:webhook,admin,manual'],
            'momo_transaction_id' => ['nullable', 'string', 'max:100'],
        ]);

        if ($payment->status === 'completed') {
            return $this->error('Payment already verified', 422);
        }

        $payment->markAsCompleted($request->get('verified_by'));

        // Only create subscription for subscription payments
        if ($payment->isSubscriptionPayment()) {
            $term = $payment->term;
            $subscription = Subscription::create([
                'parent_id' => $payment->parent_id,
                'student_id' => $payment->student_id,
                'term_id' => $payment->term_id,
                'status' => 'active',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'starts_at' => now(),
                'expires_at' => $term->end_date,
                'payment_id' => $payment->id,
            ]);

            $payment->load(['parent.user', 'student', 'term', 'subscription']);

            return $this->success([
                'payment' => $payment,
                'subscription' => $subscription,
            ], 'Payment verified and subscription created successfully');
        } else {
            // For fee payments, just return success
            $payment->load(['parent.user', 'student', 'term']);
            return $this->success([
                'payment' => $payment,
            ], 'Fee payment verified successfully');
        }
    }

    /**
     * Check payment status and auto-verify if payment is completed
     */
    public function checkStatus(Request $request, Payment $payment): JsonResponse
    {
        $parent = auth()->user()->parent;
        
        if (!$parent || $payment->parent_id !== $parent->id) {
            return $this->error('Payment not found', 404);
        }

        // If payment is pending and it's a mobile money payment, try to verify automatically
        if ($payment->status === 'pending' && $payment->payment_method === 'momo' && $payment->momo_provider) {
            try {
                $directMomoService = new DirectMomoService();
                
                $verificationResult = $directMomoService->verifyPayment(
                    $payment->momo_transaction_id ?? $payment->reference,
                    $payment->momo_provider
                );

                if ($verificationResult['success'] && $verificationResult['status'] === 'completed') {
                    // Payment verified - mark as completed
                    $payment->markAsCompleted('auto_verification');
                    
                    // Create subscription if it's a subscription payment
                    if ($payment->isSubscriptionPayment()) {
                        $existingSubscription = Subscription::where('parent_id', $payment->parent_id)
                            ->where('student_id', $payment->student_id)
                            ->where('term_id', $payment->term_id)
                            ->where('status', 'active')
                            ->first();

                        if (!$existingSubscription) {
                            $term = $payment->term;
                            Subscription::create([
                                'parent_id' => $payment->parent_id,
                                'student_id' => $payment->student_id,
                                'term_id' => $payment->term_id,
                                'status' => 'active',
                                'amount' => $payment->amount,
                                'currency' => $payment->currency,
                                'starts_at' => now(),
                                'expires_at' => $term->end_date,
                                'payment_id' => $payment->id,
                            ]);
                        }
                    }

                    \Log::info('Payment auto-verified via status check', [
                        'payment_id' => $payment->id,
                        'transaction_id' => $payment->momo_transaction_id,
                    ]);
                } elseif ($verificationResult['status'] === 'failed') {
                    $payment->status = 'failed';
                    $payment->save();
                }
                // If still pending, continue
            } catch (\Exception $e) {
                \Log::error('Auto verification error during status check', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                // Continue with current status
            }
        }

        $payment->load(['student', 'term', 'subscription']);

        return $this->success($payment, 'Payment status retrieved successfully');
    }

    /**
     * Verify payment using token and payment reference
     * This allows parents to verify their payment and auto-activate subscription
     */
    public function verifyPayment(Request $request): JsonResponse
    {
        $request->validate([
            'verification_token' => ['required', 'string'],
            'payment_reference' => ['required', 'string', 'max:100'],
        ]);

        $parent = auth()->user()->parent;
        
        if (!$parent) {
            return $this->error('Parent profile not found', 404);
        }

        // Find payment by verification token
        $payment = Payment::where('verification_token', $request->get('verification_token'))
            ->where('parent_id', $parent->id)
            ->first();

        if (!$payment) {
            return $this->error('Invalid verification token. Please check and try again.', 404);
        }

        // Check if payment is already completed
        if ($payment->status === 'completed') {
            $payment->load(['student', 'term', 'subscription']);
            return $this->success([
                'payment' => $payment,
                'message' => 'Payment already verified. Your subscription is active.',
            ], 'Payment already verified');
        }

        // Check if payment is failed or cancelled
        if (in_array($payment->status, ['failed', 'cancelled'])) {
            return $this->error('This payment has been marked as failed or cancelled. Please create a new payment.', 422);
        }

        // SECURITY FIX: Validate payment reference matches the payment
        // For MoMo payments, verify the transaction with the payment gateway
        $paymentReference = $request->get('payment_reference');
        
        if ($payment->payment_method === 'momo' && $payment->momo_transaction_id) {
            // Verify payment with payment gateway
            try {
                $momoService = new MomoPaymentService();
                $verificationResult = $momoService->verifyPayment($payment->momo_transaction_id);
                
                if (!$verificationResult['success'] || $verificationResult['status'] !== 'completed') {
                    return $this->error('Payment verification failed. The payment reference does not match a completed transaction.', 422);
                }
                
                // Verify the amount matches
                if (abs($verificationResult['amount'] - $payment->amount) > 0.01) {
                    return $this->error('Payment amount mismatch. Please contact support.', 422);
                }
                
                // Payment verified successfully via gateway
                $payment->payment_reference = $paymentReference;
                $payment->momo_transaction_id = $verificationResult['transaction_id'] ?? $payment->momo_transaction_id;
                $payment->markAsCompleted('token_verification');
            } catch (\Exception $e) {
                \Log::error('Payment Verification Error', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                
                // Fallback: Allow manual verification but log it
                \Log::warning('Manual payment verification (gateway verification failed)', [
                    'payment_id' => $payment->id,
                    'payment_reference' => $paymentReference,
                ]);
                
                $payment->payment_reference = $paymentReference;
                $payment->markAsCompleted('token_verification');
            }
        } else {
            // For non-MoMo payments, validate that payment_reference contains the verification token
            // This ensures the parent actually made a payment with the token
            if (stripos($paymentReference, $payment->verification_token) === false) {
                return $this->error('Invalid payment reference. The payment reference must contain your verification token.', 422);
            }
            
            $payment->payment_reference = $paymentReference;
            $payment->markAsCompleted('token_verification');
        }

        // Check if subscription already exists (idempotency)
        $existingSubscription = Subscription::where('parent_id', $payment->parent_id)
            ->where('student_id', $payment->student_id)
            ->where('term_id', $payment->term_id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            // Payment already processed, just return success
            $payment->load(['student', 'term', 'subscription']);
            \Log::warning('Subscription already exists for verified payment', [
                'payment_id' => $payment->id,
                'subscription_id' => $existingSubscription->id,
            ]);
            
            return $this->success([
                'payment' => $payment,
                'subscription' => $existingSubscription,
                'message' => 'Payment verified. Your subscription is already active.',
            ], 'Payment verified successfully');
        }

        // Only create subscription for subscription payments
        if ($payment->isSubscriptionPayment()) {
            // Check if subscription already exists (idempotency)
            $existingSubscription = Subscription::where('parent_id', $payment->parent_id)
                ->where('student_id', $payment->student_id)
                ->where('term_id', $payment->term_id)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                // Payment already processed, just return success
                $payment->load(['student', 'term', 'subscription']);
                \Log::warning('Subscription already exists for verified payment', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $existingSubscription->id,
                ]);
                
                return $this->success([
                    'payment' => $payment,
                    'subscription' => $existingSubscription,
                    'message' => 'Payment verified. Your subscription is already active.',
                ], 'Payment verified successfully');
            }

            // Create subscription automatically
            $term = $payment->term;
            $subscription = Subscription::create([
                'parent_id' => $payment->parent_id,
                'student_id' => $payment->student_id,
                'term_id' => $payment->term_id,
                'status' => 'active',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'starts_at' => now(),
                'expires_at' => $term->end_date,
                'payment_id' => $payment->id,
            ]);

            $payment->load(['student', 'term', 'subscription']);

            return $this->success([
                'payment' => $payment,
                'subscription' => $subscription,
                'message' => 'Payment verified successfully! Your subscription is now active.',
            ], 'Payment verified and subscription activated successfully');
        } else {
            // For fee payments, just return success
            $payment->load(['student', 'term']);
            return $this->success([
                'payment' => $payment,
                'message' => 'Fee payment verified successfully!',
            ], 'Fee payment verified successfully');
        }
    }

    /**
     * Retry failed payment
     */
    public function retry(Request $request, Payment $payment): JsonResponse
    {
        $parent = auth()->user()->parent;
        
        if (!$parent || $payment->parent_id !== $parent->id) {
            return $this->error('Payment not found', 404);
        }

        if ($payment->status !== 'failed') {
            return $this->error('Only failed payments can be retried', 422);
        }

        // Check if subscription already exists (payment might have succeeded but webhook failed)
        $existingSubscription = Subscription::where('parent_id', $parent->id)
            ->where('student_id', $payment->student_id)
            ->where('term_id', $payment->term_id)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            return $this->error('Active subscription already exists. The original payment may have succeeded.', 422);
        }

        // Create new payment with same details
        $newReference = 'PAY-' . Str::upper(Str::random(10));
        while (Payment::where('reference', $newReference)->exists()) {
            $newReference = 'PAY-' . Str::upper(Str::random(10));
        }

        $newPayment = Payment::create([
            'parent_id' => $payment->parent_id,
            'student_id' => $payment->student_id,
            'term_id' => $payment->term_id,
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'payment_method' => $payment->payment_method,
            'momo_provider' => $payment->momo_provider,
            'momo_number' => $payment->momo_number,
            'reference' => $newReference,
            'status' => 'processing',
        ]);

        try {
            // Retry payment via direct MoMo service
            if ($newPayment->payment_method === 'momo') {
                $directMomoService = new DirectMomoService();
                
                $paymentResult = $directMomoService->initiatePayment([
                    'amount' => $newPayment->amount,
                    'phone' => $newPayment->momo_number,
                    'provider' => $newPayment->momo_provider,
                    'reference' => $newPayment->reference,
                ]);

                if ($paymentResult['success']) {
                    $newPayment->status = 'pending';
                    $newPayment->momo_transaction_id = $paymentResult['transaction_id'] ?? $newPayment->reference;
                    $newPayment->webhook_data = [
                        'provider_response' => $paymentResult,
                        'instructions' => $paymentResult['instructions'] ?? null,
                    ];
                } else {
                    $newPayment->status = 'failed';
                    $newPayment->webhook_data = $paymentResult;
                }
            } else {
                $newPayment->status = 'pending';
            }
            
            $newPayment->save();
        } catch (\Exception $e) {
            $newPayment->status = 'failed';
            $newPayment->save();
            
            \Log::error('Payment retry failed', [
                'original_payment_id' => $payment->id,
                'new_payment_id' => $newPayment->id,
                'error' => $e->getMessage(),
            ]);
            
            return $this->error('Failed to retry payment. Please try again.', 500);
        }

        $newPayment->load(['student', 'term']);

        return $this->success($newPayment, 'Payment retry initiated successfully', 201);
    }

    /**
     * Handle payment webhook from payment gateway
     */
    public function webhook(Request $request): JsonResponse
    {
        // Verify webhook signature (implement based on your payment provider)
        // For Flutterwave: verify hash
        // For Paystack: verify signature
        
        $provider = config('services.payment.provider', 'flutterwave');
        
        // Get reference from request (different providers use different field names)
        $reference = $request->get('tx_ref') ?? $request->get('reference') ?? $request->get('data')['tx_ref'] ?? null;
        
        if (!$reference) {
            \Log::warning('Webhook received without reference', ['request' => $request->all()]);
            return $this->error('Missing payment reference', 400);
        }

        $payment = Payment::where('reference', $reference)->first();

        if (!$payment) {
            \Log::warning('Webhook received for unknown payment', [
                'reference' => $reference,
                'request' => $request->all(),
            ]);
            return $this->error('Payment not found', 404);
        }

        // Determine status from webhook data (different providers use different formats)
        $status = $request->get('status') ?? $request->get('data')['status'] ?? null;
        
        if ($provider === 'flutterwave') {
            $status = $request->get('data')['status'] ?? $request->get('status') ?? null;
            $transactionId = $request->get('data')['id'] ?? $request->get('id') ?? null;
        } elseif ($provider === 'paystack') {
            $status = $request->get('data')['status'] ?? $request->get('status') ?? null;
            $transactionId = $request->get('data')['reference'] ?? $request->get('reference') ?? null;
        } else {
            $status = $request->get('status') ?? 'pending';
            $transactionId = $request->get('transaction_id') ?? $request->get('id') ?? null;
        }

        // Prevent duplicate webhook processing
        if ($payment->status === 'completed' && ($status === 'successful' || $status === 'success')) {
            \Log::warning('Duplicate webhook received for completed payment', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
            ]);
            return $this->success(null, 'Payment already processed');
        }

        if ($status === 'successful' || $status === 'success') {
            // Double-check subscription doesn't already exist (idempotency)
            $existingSubscription = Subscription::where('parent_id', $payment->parent_id)
                ->where('student_id', $payment->student_id)
                ->where('term_id', $payment->term_id)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                // Payment already processed, just update payment status
                $payment->momo_transaction_id = $transactionId ?? $payment->momo_transaction_id;
                $payment->webhook_data = $request->all();
                $payment->markAsCompleted('webhook');
                
                \Log::warning('Subscription already exists for payment', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $existingSubscription->id,
                ]);
                
                return $this->success(null, 'Payment already processed');
            }

            $payment->momo_transaction_id = $transactionId ?? $payment->momo_transaction_id;
            $payment->webhook_data = $request->all();
            $payment->markAsCompleted('webhook');

            // Create subscription
            $term = $payment->term;
            Subscription::create([
                'parent_id' => $payment->parent_id,
                'student_id' => $payment->student_id,
                'term_id' => $payment->term_id,
                'status' => 'active',
                'amount' => $payment->amount,
                'currency' => $payment->currency,
                'starts_at' => now(),
                'expires_at' => $term->end_date,
                'payment_id' => $payment->id,
            ]);
        } else {
            // Payment failed
            $payment->status = 'failed';
            $payment->webhook_data = $request->all();
            $payment->save();
            
            \Log::info('Payment failed via webhook', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'status' => $status,
            ]);
        }

        return $this->success(null, 'Webhook processed successfully');
    }

    /**
     * Start automatic payment verification polling
     * This logs that verification should be done via status checks
     */
    private function startPaymentVerification(int $paymentId, string $provider): void
    {
        // Payment verification will be done automatically when status is checked
        // The frontend will poll the status endpoint, which will auto-verify
        \Log::info('Payment verification polling started', [
            'payment_id' => $paymentId,
            'provider' => $provider,
        ]);
    }
}

