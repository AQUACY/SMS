# Payment Integration Setup Guide

## Overview

The system supports two different payment flows:

1. **Fee Payments (Manual)**: Parents create a payment record and pay at the school accounts office. The accounts manager manually verifies and processes the payment.

2. **Subscription Payments (Automatic)**: Parents pay via Mobile Money using a Ghana-friendly payment gateway (Fincra Links). The system automatically sends payment prompts and processes confirmations via webhooks.

## Features Implemented

1. **Auto-fetch Fee Amount**: When a parent selects a student and term for fee payment, the system automatically fetches the applicable fee amount.

2. **Manual Fee Payment Flow**: 
   - Parents create payment records online
   - Parents pay at school accounts office
   - Accounts manager manually verifies and processes payments

3. **Automatic Subscription Payment Integration**: 
   - Supports Fincra Links (Ghana-friendly), Flutterwave, and Paystack payment gateways
   - Automatically sends payment prompts to user's mobile device
   - Handles payment confirmation via webhooks
   - Only used for subscription payments

4. **Secure Payment Verification**: 
   - For subscription MoMo payments: Verifies payment with payment gateway API
   - For fee payments: Manual verification by accounts manager
   - Prevents unauthorized payment verification

5. **Automatic Subscription Processing**: 
   - Webhook endpoint receives payment confirmations
   - Automatically creates subscriptions for subscription payments
   - Updates payment status in real-time

## Environment Configuration

The system now uses **Direct Mobile Money** integration by default, which works without third-party payment gateways.

### Basic Setup (No API Credentials Required)

The system works out of the box with USSD instructions. Parents receive payment instructions and the system automatically verifies payments when status is checked.

```env
# Payment Provider (default is 'direct' - no third-party gateway needed)
PAYMENT_PROVIDER=direct
```

### Optional: Direct Mobile Money API Credentials

If you have direct API access to mobile money providers (MTN, Vodafone, AirtelTigo), you can add credentials for automatic payment prompts:

```env
# MTN Mobile Money API (Optional - for automatic payment prompts)
MTN_API_KEY=your_mtn_api_key
MTN_API_SECRET=your_mtn_api_secret
MTN_MERCHANT_ID=your_mtn_merchant_id

# Vodafone Cash API (Optional)
VODAFONE_API_KEY=your_vodafone_api_key
VODAFONE_API_SECRET=your_vodafone_api_secret

# AirtelTigo Money API (Optional)
AIRTELTIGO_API_KEY=your_airteltigo_api_key
AIRTELTIGO_API_SECRET=your_airteltigo_api_secret
```

**Important**: These credentials are **OPTIONAL**. The system works perfectly without them by providing USSD instructions and automatically verifying payments when status is checked.

## How to Obtain Mobile Money API Credentials

### MTN Mobile Money API

1. **Register on MTN Developer Portal:**
   - Visit: https://developers.mtn.com/
   - Create an account and complete registration
   - Navigate to "Apps" section and create a new application
   - Your API Key will be generated in the "apps" section under your profile

2. **Merchant ID:**
   - Typically assigned during the onboarding process
   - Contact MTN support if not available in your dashboard

3. **Documentation:**
   - Review API documentation at: https://developers.mtn.com/

### Vodafone Cash API

1. **Contact Vodafone Business Services:**
   - Reach out to Vodafone Ghana's business support or partnerships team
   - Email: business@vodafone.com.gh (or check their website for current contact)

2. **Merchant Registration:**
   - Submit business registration documents
   - Provide required business information
   - Upon approval, you'll receive Merchant ID and API Key

3. **Note:** Vodafone's API is not publicly available - requires direct partnership

### AirtelTigo Money API

1. **Contact AirtelTigo Business Support:**
   - Reach out to AirtelTigo Ghana's business services department
   - Email: business@airteltigo.com.gh (or check their website for current contact)

2. **Merchant Registration:**
   - Submit business details and required documentation
   - After approval, you'll receive Merchant ID and API Key

3. **Note:** AirtelTigo's API requires direct engagement with their business team

## Alternative: Payment Aggregators

If obtaining direct API access is challenging, consider using third-party payment aggregators that support multiple mobile money services:

### Recommended Aggregators:

1. **iPay Ghana**
   - Website: https://ipaygh.com
   - Documentation: https://docs.ipaygh.com
   - Supports: MTN, Vodafone, AirtelTigo, VISA, MasterCard
   - Provides unified API for all networks

2. **TheTeller**
   - Website: https://theteller.net
   - Documentation: https://theteller.net/documentation
   - Supports multiple payment channels

3. **NALO Solutions**
   - Website: https://www.nalosolutions.com
   - Documentation: https://www.nalosolutions.com/developers/
   - Supports multiple networks

4. **Korba Xchange**
   - Website: https://xchange.korba365.com
   - Documentation: https://xchange.korba365.com/docs/
   - Client ID available in merchant dashboard

**Benefits of Using Aggregators:**
- Single API for all mobile money providers
- Easier integration process
- Unified dashboard for all transactions
- Often faster approval process

**Note**: If you use an aggregator, you'll configure it as a payment provider (like Fincra/Flutterwave) rather than using the direct mobile money credentials.

### Alternative: Third-Party Payment Gateways

If you prefer using payment gateways, you can configure:

```env
# Payment Gateway Configuration (for subscription payments only)
PAYMENT_PROVIDER=fincra  # Options: 'fincra', 'flutterwave', 'paystack', or 'direct'
PAYMENT_API_KEY=your_api_key_here
PAYMENT_API_SECRET=your_api_secret_here
PAYMENT_MERCHANT_ID=your_merchant_id_here  # Optional, depending on provider
PAYMENT_BASE_URL=https://api.fincra.com  # Fincra default
# For Flutterwave: https://api.flutterwave.com/v3
# For Paystack: https://api.paystack.co
```

### Fincra Links Setup (Recommended for Ghana)

1. Sign up at [Fincra](https://fincra.com)
2. Get your API keys from the dashboard (Profile → API Keys)
3. Set `PAYMENT_PROVIDER=fincra`
4. Set `PAYMENT_API_KEY` to your **Fincra Secret Key**
5. Set `PAYMENT_BASE_URL=https://api.fincra.com`

**Note**: Fincra Links is Ghana-friendly and supports MTN Mobile Money, Vodafone Cash, and AirtelTigo Money.

### Flutterwave Setup

1. Sign up at [Flutterwave](https://flutterwave.com)
2. Get your API keys from the dashboard (Settings → API Keys)
3. Set `PAYMENT_PROVIDER=flutterwave`
4. Set `PAYMENT_API_KEY` to your **Flutterwave SECRET KEY** (NOT the public key)
   - **Secret Key**: `FLWSECK_TEST-xxxxx` (for test) or `FLWSECK-xxxxx` (for live)
   - This is the key that starts with `FLWSECK_TEST` or `FLWSECK`
   - **DO NOT use the Public Key** (`FLWPUBK_TEST-xxxxx`) - that's for client-side only
   - **DO NOT use the Encryption Key** - that's for encrypting sensitive data
5. Set `PAYMENT_BASE_URL=https://api.flutterwave.com/v3`

**Important**: Flutterwave provides 3 keys:
- **Public Key** (`FLWPUBK_TEST-...`): Used for client-side integrations (browser/mobile apps). NOT for server-side API calls.
- **Secret Key** (`FLWSECK_TEST-...`): Used for server-side API calls. **This is what you need!**
- **Encryption Key** (`FLWSECK_TEST0a05bdc2560d`): Used for encrypting sensitive data. NOT for API authentication.

### Paystack Setup

1. Sign up at [Paystack](https://paystack.com)
2. Get your API keys from the dashboard
3. Set `PAYMENT_PROVIDER=paystack`
4. Set `PAYMENT_API_KEY` to your Paystack secret key
5. Set `PAYMENT_BASE_URL=https://api.paystack.co`

## Webhook Configuration

### Flutterwave Webhook

1. Go to Flutterwave Dashboard → Settings → Webhooks
2. Add webhook URL: `https://yourdomain.com/api/payments/webhook`
3. Select events: `charge.completed`, `charge.failed`
4. Copy the webhook secret (if provided) and add to `.env` as `PAYMENT_WEBHOOK_SECRET`

### Paystack Webhook

1. Go to Paystack Dashboard → Settings → Webhooks
2. Add webhook URL: `https://yourdomain.com/api/payments/webhook`
3. Select events: `charge.success`, `charge.failed`
4. Copy the webhook secret (if provided) and add to `.env` as `PAYMENT_WEBHOOK_SECRET`

## Payment Flow

### For Fee Payments (Manual)

1. Parent selects student and term
2. System automatically fetches fee amount
3. Parent clicks "Pay School Fees" button
4. System creates a pending payment record
5. Parent visits school accounts office to make payment
6. Accounts manager verifies payment and marks it as completed
7. Parent can view payment status in their dashboard

### For Subscription Payments (Direct Mobile Money)

1. Parent selects student, term, and payment method (MoMo)
2. System automatically fetches subscription price
3. Parent enters MoMo number and provider (MTN, Vodafone, or AirtelTigo)
4. Parent clicks "Subscribe" button
5. System creates payment record and provides USSD instructions:
   - **MTN**: Dial *170# and follow prompts
   - **Vodafone**: Dial *110# and follow prompts
   - **AirtelTigo**: Dial *110# and follow prompts
6. Parent completes payment via USSD on their mobile device
7. System automatically checks payment status every 5 seconds
8. When payment is detected, system automatically verifies and creates subscription
9. Parent sees payment status update in real-time
10. Subscription is activated automatically

**How Automatic Verification Works:**
- Frontend polls payment status every 5 seconds
- Backend automatically verifies payment with mobile money provider on each status check
- If payment is completed, system marks payment as verified and creates subscription
- No manual intervention required!

## Security Features

1. **Payment Reference Validation**: 
   - For MoMo payments: Verifies with payment gateway API
   - For manual payments: Ensures payment reference contains verification token

2. **Duplicate Payment Prevention**: 
   - Checks for pending payments within 30 minutes
   - Checks for recently completed payments within 5 minutes

3. **Webhook Verification**: 
   - Webhook endpoint validates payment status
   - Prevents duplicate processing
   - Idempotent subscription creation

## Testing

### Test Mode

Both Flutterwave and Paystack support test mode:

- **Flutterwave**: Use test API keys from dashboard
- **Paystack**: Use test API keys from dashboard

### Test Phone Numbers

- **MTN**: Use test numbers provided by payment gateway
- **Vodafone**: Use test numbers provided by payment gateway
- **AirtelTigo**: Use test numbers provided by payment gateway

## Troubleshooting

### Payment Not Prompting on Mobile

1. Check that `PAYMENT_API_KEY` is correctly set
2. Verify phone number format (should include country code: 233XXXXXXXXX)
3. Check payment gateway logs for errors
4. Ensure webhook URL is accessible

### Webhook Not Receiving Callbacks

1. Verify webhook URL is publicly accessible
2. Check webhook configuration in payment gateway dashboard
3. Review server logs for webhook requests
4. Ensure webhook endpoint is not blocked by firewall

### Payment Verification Failing

1. Check that payment gateway API is accessible
2. Verify API credentials are correct
3. Review payment gateway API documentation for status codes
4. Check application logs for detailed error messages

## API Endpoints

- `POST /api/parent/payments` - Initiate payment
- `POST /api/payments/webhook` - Payment gateway webhook
- `POST /api/parent/payments/verify` - Manual payment verification
- `GET /api/parent/payments/{payment}/status` - Check payment status
- `POST /api/parent/payments/{payment}/retry` - Retry failed payment

## Notes

- The system supports both automatic (MoMo) and manual (bank, cash) payment methods
- Manual verification is still available for non-MoMo payments
- Payment status is polled every 5 seconds for up to 5 minutes
- Failed payments can be retried automatically

