<?php

namespace App\Http\Controllers\Api;

use App\Models\Payment;
use App\Models\Subscription;
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
        $query = Payment::whereHas('student', function ($q) use ($request) {
            $q->where('school_id', $request->get('school_id'));
        })->with(['parent.user', 'student', 'term']);

        if ($request->has('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->get('parent_id'));
        }

        if ($request->has('student_id')) {
            $query->where('student_id', $request->get('student_id'));
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
        $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'term_id' => ['required', 'exists:terms,id'],
            'amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:momo,bank,cash,other'],
            'momo_provider' => ['required_if:payment_method,momo', 'in:mtn,vodafone,airteltigo'],
            'momo_number' => ['required_if:payment_method,momo', 'string', 'max:20'],
        ]);

        $parent = auth()->user()->parent;
        
        if (!$parent) {
            return $this->error('Parent profile not found', 404);
        }

        $studentId = $request->get('student_id');
        $termId = $request->get('term_id');

        // Check if subscription already exists
        $existingSubscription = Subscription::where('parent_id', $parent->id)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'active')
            ->first();

        if ($existingSubscription) {
            return $this->error('Active subscription already exists for this student and term', 422);
        }

        // CRITICAL: Check for duplicate pending/processing payments
        // This prevents multiple payments for the same service
        $existingPendingPayment = Payment::where('parent_id', $parent->id)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->whereIn('status', ['pending', 'processing'])
            ->where('created_at', '>', now()->subMinutes(30)) // Within last 30 minutes
            ->first();

        if ($existingPendingPayment) {
            return $this->error([
                'message' => 'A payment is already in progress for this student and term. Please wait for it to complete or check your payment status.',
                'payment_id' => $existingPendingPayment->id,
                'payment_reference' => $existingPendingPayment->reference,
                'payment_status' => $existingPendingPayment->status,
            ], 422);
        }

        // Check for recently completed payment (within last 5 minutes) to prevent accidental duplicates
        $recentCompletedPayment = Payment::where('parent_id', $parent->id)
            ->where('student_id', $studentId)
            ->where('term_id', $termId)
            ->where('status', 'completed')
            ->where('created_at', '>', now()->subMinutes(5))
            ->first();

        if ($recentCompletedPayment) {
            return $this->error([
                'message' => 'A payment was recently completed for this student and term. Please check your subscriptions.',
                'payment_id' => $recentCompletedPayment->id,
                'payment_reference' => $recentCompletedPayment->reference,
            ], 422);
        }

        // Generate unique reference
        $reference = 'PAY-' . Str::upper(Str::random(10));
        
        // Ensure reference is unique (very rare collision, but safety first)
        while (Payment::where('reference', $reference)->exists()) {
            $reference = 'PAY-' . Str::upper(Str::random(10));
        }

        // Create payment with 'processing' status initially to prevent race conditions
        $payment = Payment::create([
            'parent_id' => $parent->id,
            'student_id' => $studentId,
            'term_id' => $termId,
            'amount' => $request->get('amount'),
            'currency' => 'GHS',
            'payment_method' => $request->get('payment_method'),
            'momo_provider' => $request->get('momo_provider'),
            'momo_number' => $request->get('momo_number'),
            'reference' => $reference,
            'status' => 'processing', // Set to processing immediately to prevent duplicates
        ]);

        // TODO: Integrate with Mobile Money API
        // For now, simulate API call and set back to pending
        // In production, this would make the actual API call to the mobile money provider
        try {
            // Simulate API call delay
            // In production: $momoResponse = $this->initiateMobileMoneyPayment($payment);
            
            // For now, set status back to pending for manual processing
            // In production, status would be set based on API response
            $payment->status = 'pending';
            $payment->save();
            
        } catch (\Exception $e) {
            // If API call fails, mark as failed
            $payment->status = 'failed';
            $payment->save();
            
            \Log::error('Mobile Money API call failed', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
                'error' => $e->getMessage(),
            ]);
            
            return $this->error('Failed to initiate payment. Please try again.', 500);
        }

        $payment->load(['student', 'term']);

        return $this->success($payment, 'Payment initiated successfully', 201);
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

        // Create subscription
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
    }

    /**
     * Check payment status
     */
    public function checkStatus(Request $request, Payment $payment): JsonResponse
    {
        $parent = auth()->user()->parent;
        
        if (!$parent || $payment->parent_id !== $parent->id) {
            return $this->error('Payment not found', 404);
        }

        $payment->load(['student', 'term', 'subscription']);

        return $this->success($payment, 'Payment status retrieved successfully');
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
            // TODO: Integrate with Mobile Money API
            $newPayment->status = 'pending';
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
     * Handle payment webhook
     */
    public function webhook(Request $request): JsonResponse
    {
        // TODO: Implement webhook verification
        // This should verify the webhook signature and process the payment

        $request->validate([
            'reference' => ['required', 'string'],
            'transaction_id' => ['required', 'string'],
            'status' => ['required', 'in:success,failed'],
        ]);

        $payment = Payment::where('reference', $request->get('reference'))->first();

        if (!$payment) {
            return $this->error('Payment not found', 404);
        }

        // Prevent duplicate webhook processing
        if ($payment->status === 'completed' && $request->get('status') === 'success') {
            \Log::warning('Duplicate webhook received for completed payment', [
                'payment_id' => $payment->id,
                'reference' => $payment->reference,
            ]);
            return $this->success(null, 'Payment already processed');
        }

        if ($request->get('status') === 'success') {
            // Double-check subscription doesn't already exist (idempotency)
            $existingSubscription = Subscription::where('parent_id', $payment->parent_id)
                ->where('student_id', $payment->student_id)
                ->where('term_id', $payment->term_id)
                ->where('status', 'active')
                ->first();

            if ($existingSubscription) {
                // Payment already processed, just update payment status
                $payment->momo_transaction_id = $request->get('transaction_id');
                $payment->webhook_data = $request->all();
                $payment->markAsCompleted('webhook');
                
                \Log::warning('Subscription already exists for payment', [
                    'payment_id' => $payment->id,
                    'subscription_id' => $existingSubscription->id,
                ]);
                
                return $this->success(null, 'Payment already processed');
            }

            $payment->momo_transaction_id = $request->get('transaction_id');
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
            $payment->status = 'failed';
            $payment->webhook_data = $request->all();
            $payment->save();
        }

        return $this->success(null, 'Webhook processed successfully');
    }
}

