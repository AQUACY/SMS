# Mobile Money Payment Integration Documentation

## Overview

This document outlines the complete mobile money payment integration process for the School Management System, including fail-safes, duplicate payment prevention, retry mechanisms, and webhook handling.

## Table of Contents

1. [Payment Flow](#payment-flow)
2. [Duplicate Payment Prevention](#duplicate-payment-prevention)
3. [Payment Status Management](#payment-status-management)
4. [Retry Mechanism](#retry-mechanism)
5. [Webhook Integration](#webhook-integration)
6. [Frontend Implementation](#frontend-implementation)
7. [Backend Implementation](#backend-implementation)
8. [API Integration Guide](#api-integration-guide)
9. [Testing & Troubleshooting](#testing--troubleshooting)

---

## Payment Flow

### 1. Payment Initiation

**Frontend Process:**
1. Parent fills payment form (student, term, amount, payment method)
2. Form validation ensures all required fields are filled
3. Submit button is disabled immediately upon click to prevent multiple submissions
4. Payment request is sent to backend

**Backend Process:**
1. Validate payment data
2. Check for existing active subscription (prevents duplicate subscriptions)
3. **CRITICAL:** Check for pending/processing payments within last 30 minutes (prevents duplicate payments)
4. Check for recently completed payments within last 5 minutes (prevents accidental duplicates)
5. Generate unique payment reference
6. Create payment record with status `processing`
7. Initiate mobile money API call
8. Update payment status based on API response:
   - `pending` - Waiting for user approval on mobile device
   - `failed` - API call failed or user declined
   - `processing` - Payment initiated, waiting for webhook

### 2. Payment Processing

**Mobile Money Provider Flow:**
1. System sends payment request to mobile money provider API
2. Provider sends push notification to user's mobile device
3. User approves/declines payment on mobile device
4. Provider processes payment
5. Provider sends webhook notification to our system

**System Response:**
1. Webhook endpoint receives notification
2. Verify webhook signature (security)
3. Check payment status (success/failed)
4. Update payment record
5. If successful:
   - Mark payment as `completed`
   - Create subscription record
   - Send confirmation notification to parent

### 3. Payment Completion

**Success:**
- Payment status: `completed`
- Subscription created and activated
- Parent receives confirmation
- Access to child's academic records enabled

**Failure:**
- Payment status: `failed`
- Parent can retry payment
- No subscription created
- Payment record retained for audit

---

## Duplicate Payment Prevention

### Backend Safeguards

#### 1. Active Subscription Check
```php
// Prevents creating payment if subscription already exists
$existingSubscription = Subscription::where('parent_id', $parent->id)
    ->where('student_id', $studentId)
    ->where('term_id', $termId)
    ->where('status', 'active')
    ->first();
```

#### 2. Pending/Processing Payment Check
```php
// Prevents multiple payments for same service within 30 minutes
$existingPendingPayment = Payment::where('parent_id', $parent->id)
    ->where('student_id', $studentId)
    ->where('term_id', $termId)
    ->whereIn('status', ['pending', 'processing'])
    ->where('created_at', '>', now()->subMinutes(30))
    ->first();
```

#### 3. Recent Completed Payment Check
```php
// Prevents accidental duplicate payments within 5 minutes
$recentCompletedPayment = Payment::where('parent_id', $parent->id)
    ->where('student_id', $studentId)
    ->where('term_id', $termId)
    ->where('status', 'completed')
    ->where('created_at', '>', now()->subMinutes(5))
    ->first();
```

#### 4. Unique Reference Generation
```php
// Ensures each payment has unique reference
$reference = 'PAY-' . Str::upper(Str::random(10));
while (Payment::where('reference', $reference)->exists()) {
    $reference = 'PAY-' . Str::upper(Str::random(10));
}
```

#### 5. Webhook Idempotency
```php
// Prevents duplicate webhook processing
if ($payment->status === 'completed' && $request->get('status') === 'success') {
    return $this->success(null, 'Payment already processed');
}
```

### Frontend Safeguards

#### 1. Button State Management
- Submit button disabled immediately on click
- Loading state prevents multiple clicks
- `paymentSubmitted` flag prevents form resubmission

#### 2. Form Disable After Submission
- All form fields disabled after successful submission
- Prevents accidental changes during processing

#### 3. Error Handling
- Detects duplicate payment errors from backend
- Shows existing payment information
- Provides option to check payment status

---

## Payment Status Management

### Payment Statuses

1. **`pending`** - Payment created, waiting for user action
2. **`processing`** - Payment initiated, API call in progress
3. **`completed`** - Payment successful, subscription created
4. **`failed`** - Payment failed or declined
5. **`cancelled`** - Payment cancelled by user or admin

### Status Polling

**Frontend Implementation:**
- Polls payment status every 5 seconds after submission
- Stops polling when payment is `completed` or `failed`
- Auto-stops after 5 minutes (timeout)
- Provides manual "Check Status" button

**Backend Endpoint:**
```
GET /api/parent/payments/{payment}/status
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "reference": "PAY-ABC123XYZ",
    "status": "pending",
    "amount": "100.00",
    "currency": "GHS",
    "student": {...},
    "term": {...},
    "subscription": null
  }
}
```

---

## Retry Mechanism

### When to Retry

- Payment status is `failed`
- Network error during payment initiation
- User declined payment but wants to retry
- Webhook timeout (payment succeeded but webhook not received)

### Retry Process

**Backend:**
1. Verify payment status is `failed`
2. Check if subscription already exists (payment might have succeeded)
3. Create new payment with same details
4. Generate new unique reference
5. Initiate new mobile money API call

**Frontend:**
1. Detect failed payment status
2. Show retry dialog to user
3. Call retry endpoint
4. Start status polling for new payment

**API Endpoint:**
```
POST /api/parent/payments/{payment}/retry
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 2,
    "reference": "PAY-NEW123XYZ",
    "status": "pending",
    ...
  }
}
```

---

## Webhook Integration

### Webhook Endpoint

```
POST /api/payments/webhook
```

### Webhook Security

**Required:**
1. **Signature Verification** - Verify webhook signature from provider
2. **IP Whitelist** - Only accept webhooks from provider's IP addresses
3. **HTTPS Only** - Webhook endpoint must use HTTPS
4. **Idempotency** - Handle duplicate webhooks gracefully

### Webhook Payload

**Expected Format:**
```json
{
  "reference": "PAY-ABC123XYZ",
  "transaction_id": "MTN123456789",
  "status": "success",
  "amount": "100.00",
  "currency": "GHS",
  "timestamp": "2025-01-01T12:00:00Z"
}
```

### Webhook Processing

1. **Validate Request**
   - Verify signature
   - Check IP whitelist
   - Validate payload structure

2. **Find Payment**
   - Look up payment by reference
   - Verify payment exists

3. **Check Status**
   - Prevent duplicate processing
   - Handle idempotency

4. **Process Payment**
   - Update payment status
   - Store transaction ID
   - Create subscription if successful

5. **Response**
   - Return success/error to provider
   - Log all webhook events

### Webhook Idempotency

```php
// Prevent duplicate webhook processing
if ($payment->status === 'completed' && $request->get('status') === 'success') {
    \Log::warning('Duplicate webhook received', [
        'payment_id' => $payment->id,
        'reference' => $payment->reference,
    ]);
    return $this->success(null, 'Payment already processed');
}
```

---

## Frontend Implementation

### Payment Form Component

**Key Features:**
- Form validation
- Button debouncing
- Duplicate submission prevention
- Status polling
- Error handling
- Retry mechanism

**State Management:**
```javascript
const submitting = ref(false);
const paymentSubmitted = ref(false);
const currentPaymentId = ref(null);
const paymentStatusInterval = ref(null);
```

**Submit Handler:**
```javascript
async function submitPayment() {
  // Prevent multiple submissions
  if (submitting.value || paymentSubmitted.value) {
    return;
  }

  submitting.value = true;
  paymentSubmitted.value = true;

  try {
    // Submit payment
    const response = await api.post('/parent/payments', paymentData);
    
    if (response.data.success) {
      // Start status polling
      startPaymentStatusPolling(response.data.data.id);
    }
  } catch (error) {
    // Handle errors
    paymentSubmitted.value = false; // Allow retry
  } finally {
    submitting.value = false;
  }
}
```

**Status Polling:**
```javascript
function startPaymentStatusPolling(paymentId) {
  paymentStatusInterval.value = setInterval(async () => {
    const response = await api.get(`/parent/payments/${paymentId}/status`);
    const payment = response.data.data;
    
    if (payment.status === 'completed') {
      clearInterval(paymentStatusInterval.value);
      // Show success and redirect
    } else if (payment.status === 'failed') {
      clearInterval(paymentStatusInterval.value);
      // Show retry dialog
    }
  }, 5000); // Poll every 5 seconds
}
```

---

## Backend Implementation

### Payment Controller

**Key Methods:**
1. `store()` - Create new payment
2. `checkStatus()` - Get payment status
3. `retry()` - Retry failed payment
4. `webhook()` - Handle webhook notifications
5. `verify()` - Manual payment verification (admin)

### Database Schema

**Payments Table:**
- `id` - Primary key
- `parent_id` - Foreign key to parents
- `student_id` - Foreign key to students
- `term_id` - Foreign key to terms
- `amount` - Decimal(10,2)
- `currency` - String(3)
- `payment_method` - Enum (momo, bank, cash, other)
- `momo_provider` - String(50) nullable
- `momo_transaction_id` - String(100) nullable
- `reference` - String(100) unique
- `status` - Enum (pending, processing, completed, failed, cancelled)
- `webhook_data` - JSON nullable
- `verified_at` - Timestamp nullable
- `verified_by` - String(50) nullable

**Indexes:**
- `parent_id`
- `student_id`
- `term_id`
- `status`
- `reference` (unique)
- `momo_transaction_id`

---

## API Integration Guide

### Mobile Money Provider Integration

#### 1. Choose Provider

**Supported Providers:**
- MTN Mobile Money
- Vodafone Cash
- AirtelTigo Money

#### 2. API Credentials

Store credentials securely in `.env`:
```env
MTN_API_KEY=your_api_key
MTN_API_SECRET=your_api_secret
MTN_MERCHANT_ID=your_merchant_id
MTN_WEBHOOK_SECRET=your_webhook_secret

VODAFONE_API_KEY=your_api_key
VODAFONE_API_SECRET=your_api_secret
VODAFONE_MERCHANT_ID=your_merchant_id
VODAFONE_WEBHOOK_SECRET=your_webhook_secret

AIRTELTIGO_API_KEY=your_api_key
AIRTELTIGO_API_SECRET=your_api_secret
AIRTELTIGO_MERCHANT_ID=your_merchant_id
AIRTELTIGO_WEBHOOK_SECRET=your_webhook_secret
```

#### 3. Implement Payment Service

Create `app/Services/MobileMoneyService.php`:

```php
<?php

namespace App\Services;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobileMoneyService
{
    public function initiatePayment(Payment $payment)
    {
        $provider = $payment->momo_provider;
        
        switch ($provider) {
            case 'mtn':
                return $this->initiateMTNPayment($payment);
            case 'vodafone':
                return $this->initiateVodafonePayment($payment);
            case 'airteltigo':
                return $this->initiateAirtelTigoPayment($payment);
            default:
                throw new \Exception("Unsupported provider: {$provider}");
        }
    }

    private function initiateMTNPayment(Payment $payment)
    {
        $apiKey = config('services.mtn.api_key');
        $apiSecret = config('services.mtn.api_secret');
        $merchantId = config('services.mtn.merchant_id');
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->getMTNToken($apiKey, $apiSecret),
            'Content-Type' => 'application/json',
        ])->post('https://api.mtn.com/v1/collections', [
            'amount' => $payment->amount,
            'currency' => $payment->currency,
            'externalId' => $payment->reference,
            'payer' => [
                'partyIdType' => 'MSISDN',
                'partyId' => $payment->momo_number,
            ],
            'payerMessage' => "Payment for {$payment->student->full_name} - {$payment->term->name}",
            'payeeNote' => "School subscription payment",
        ]);

        if ($response->successful()) {
            $data = $response->json();
            // Update payment with transaction ID if provided
            if (isset($data['transactionId'])) {
                $payment->momo_transaction_id = $data['transactionId'];
                $payment->save();
            }
            return true;
        }

        throw new \Exception("MTN API Error: " . $response->body());
    }

    private function getMTNToken($apiKey, $apiSecret)
    {
        // Implement token generation logic
        // This is a simplified example
        $response = Http::withBasicAuth($apiKey, $apiSecret)
            ->post('https://api.mtn.com/v1/oauth/token');
        
        return $response->json()['access_token'];
    }

    // Similar methods for Vodafone and AirtelTigo
}
```

#### 4. Update Payment Controller

```php
use App\Services\MobileMoneyService;

public function store(Request $request): JsonResponse
{
    // ... validation and duplicate checks ...
    
    $payment = Payment::create([...]);
    
    try {
        if ($payment->payment_method === 'momo') {
            $mobileMoneyService = new MobileMoneyService();
            $mobileMoneyService->initiatePayment($payment);
            $payment->status = 'pending'; // Waiting for user approval
        } else {
            $payment->status = 'pending'; // Manual processing
        }
        $payment->save();
    } catch (\Exception $e) {
        $payment->status = 'failed';
        $payment->save();
        Log::error('Payment initiation failed', [
            'payment_id' => $payment->id,
            'error' => $e->getMessage(),
        ]);
        return $this->error('Failed to initiate payment', 500);
    }
    
    return $this->success($payment, 'Payment initiated successfully', 201);
}
```

#### 5. Webhook Verification

```php
public function webhook(Request $request): JsonResponse
{
    // Verify webhook signature
    $signature = $request->header('X-Webhook-Signature');
    $payload = $request->getContent();
    $expectedSignature = hash_hmac('sha256', $payload, config('services.mtn.webhook_secret'));
    
    if (!hash_equals($expectedSignature, $signature)) {
        Log::warning('Invalid webhook signature', [
            'ip' => $request->ip(),
        ]);
        return $this->error('Invalid signature', 401);
    }
    
    // Process webhook
    // ...
}
```

---

## Testing & Troubleshooting

### Testing Scenarios

1. **Normal Payment Flow**
   - Submit payment
   - Approve on mobile device
   - Verify subscription created

2. **Duplicate Payment Prevention**
   - Submit payment
   - Try to submit again immediately
   - Verify error message shown

3. **Payment Failure**
   - Submit payment
   - Decline on mobile device
   - Verify retry option available

4. **Network Failure**
   - Submit payment during network outage
   - Verify error handling
   - Verify retry mechanism

5. **Webhook Testing**
   - Simulate webhook call
   - Verify payment status updated
   - Verify subscription created

### Common Issues

**Issue: Payment stuck in "pending" status**
- **Solution:** Check webhook endpoint is accessible
- **Solution:** Verify webhook signature validation
- **Solution:** Check payment provider dashboard

**Issue: Duplicate payments created**
- **Solution:** Verify duplicate checks are working
- **Solution:** Check database indexes
- **Solution:** Review frontend button state management

**Issue: Webhook not received**
- **Solution:** Verify webhook URL is correct
- **Solution:** Check firewall/security settings
- **Solution:** Verify webhook endpoint is public

**Issue: Payment succeeded but subscription not created**
- **Solution:** Check webhook processing logs
- **Solution:** Verify subscription creation logic
- **Solution:** Manually verify payment if needed

### Monitoring

**Key Metrics to Monitor:**
- Payment success rate
- Average payment processing time
- Failed payment rate
- Webhook delivery rate
- Duplicate payment attempts

**Logging:**
- All payment creation attempts
- All webhook calls
- All payment status changes
- All errors and exceptions

---

## Security Considerations

1. **API Keys** - Store securely, never commit to repository
2. **Webhook Signatures** - Always verify webhook signatures
3. **HTTPS Only** - All API calls must use HTTPS
4. **Rate Limiting** - Implement rate limiting on payment endpoints
5. **Input Validation** - Validate all user inputs
6. **SQL Injection** - Use parameterized queries
7. **XSS Protection** - Sanitize all user inputs
8. **CSRF Protection** - Use CSRF tokens for forms

---

## Conclusion

This payment system provides:
- ✅ Duplicate payment prevention
- ✅ Retry mechanisms
- ✅ Status polling
- ✅ Webhook handling
- ✅ Error recovery
- ✅ Security measures
- ✅ Comprehensive logging

For questions or issues, refer to the troubleshooting section or contact the development team.

