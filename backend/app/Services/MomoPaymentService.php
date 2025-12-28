<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MomoPaymentService
{
    private $apiKey;
    private $apiSecret;
    private $merchantId;
    private $baseUrl;
    private $provider;

    public function __construct()
    {
        // Get payment gateway configuration from environment
        // Supports Fincra, Flutterwave, Paystack, or direct MoMo API
        $this->provider = config('services.payment.provider', 'fincra'); // 'fincra', 'flutterwave', 'paystack', or 'direct'
        $this->apiKey = config('services.payment.api_key');
        $this->apiSecret = config('services.payment.api_secret');
        $this->merchantId = config('services.payment.merchant_id');
        $this->baseUrl = config('services.payment.base_url');
        
        // Validate API key is set
        if (empty($this->apiKey)) {
            throw new \Exception('Payment API key is not configured. Please set PAYMENT_API_KEY in your .env file.');
        }
    }

    /**
     * Get HTTP client with SSL configuration
     */
    private function getHttpClient()
    {
        $verify = config('services.payment.verify_ssl');
        
        // Auto-disable SSL verification in local/development environments if not explicitly set
        if ($verify === null) {
            $verify = !in_array(app()->environment(), ['local', 'development', 'testing']);
        }
        
        // If SSL verification is disabled, use withoutVerifying()
        // WARNING: Only disable SSL verification in development/testing environments
        if (!$verify) {
            return Http::withoutVerifying();
        }
        
        // Otherwise, use default HTTP client with SSL verification enabled
        return Http::withOptions([
            'verify' => true,
        ]);
    }

    /**
     * Initiate a mobile money payment
     * 
     * @param array $data Payment data (amount, phone, provider, reference, etc.)
     * @return array Response with transaction_id, status, and message
     */
    public function initiatePayment(array $data): array
    {
        try {
            $amount = $data['amount'];
            $phone = $this->formatPhoneNumber($data['phone'], $data['provider']);
            $reference = $data['reference'];
            $provider = $data['provider']; // mtn, vodafone, airteltigo
            $callbackUrl = $data['callback_url'] ?? route('api.payments.webhook');

            switch ($this->provider) {
                case 'fincra':
                    return $this->initiateFincraPayment($amount, $phone, $reference, $provider, $callbackUrl);
                
                case 'flutterwave':
                    return $this->initiateFlutterwavePayment($amount, $phone, $reference, $provider, $callbackUrl);
                
                case 'paystack':
                    return $this->initiatePaystackPayment($amount, $phone, $reference, $provider, $callbackUrl);
                
                case 'direct':
                    return $this->initiateDirectMomoPayment($amount, $phone, $reference, $provider, $callbackUrl);
                
                default:
                    throw new \Exception("Unsupported payment provider: {$this->provider}");
            }
        } catch (\Exception $e) {
            Log::error('MoMo Payment Initiation Failed', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
                'transaction_id' => null,
            ];
        }
    }

    /**
     * Initiate payment via Fincra Links (Ghana-friendly)
     */
    private function initiateFincraPayment($amount, $phone, $reference, $provider, $callbackUrl): array
    {
        // Map provider to Fincra network code
        $networkMap = [
            'mtn' => 'MTN',
            'vodafone' => 'VODAFONE',
            'airteltigo' => 'AIRTELTIGO',
        ];

        // Fincra uses payment links API
        $response = $this->getHttpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/payment-links', [
            'amount' => $amount,
            'currency' => 'GHS',
            'description' => 'Subscription Payment - ' . $reference,
            'customer' => [
                'email' => auth()->user()->email ?? 'customer@example.com',
                'phone' => $phone,
            ],
            'paymentMethods' => ['mobile_money'],
            'mobileMoney' => [
                'network' => $networkMap[$provider] ?? 'MTN',
            ],
            'redirectUrl' => $callbackUrl,
            'webhookUrl' => $callbackUrl,
            'reference' => $reference,
        ]);

        $responseData = $response->json();

        // Check for authentication errors
        if ($response->status() === 401 || $response->status() === 403) {
            Log::error('Fincra Authentication Failed', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);
            
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Invalid API key. Please check your Fincra Secret Key in the .env file.',
                'transaction_id' => null,
            ];
        }

        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment prompt sent to your mobile device. Please approve the payment.',
                'transaction_id' => $responseData['data']['id'] ?? $reference,
                'payment_link' => $responseData['data']['paymentLink'] ?? null,
            ];
        }

        // Log the full response for debugging
        Log::warning('Fincra Payment Initiation Failed', [
            'status_code' => $response->status(),
            'response' => $responseData,
        ]);

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $responseData['message'] ?? 'Failed to initiate payment',
            'transaction_id' => null,
        ];
    }

    /**
     * Initiate payment via Flutterwave
     */
    private function initiateFlutterwavePayment($amount, $phone, $reference, $provider, $callbackUrl): array
    {
        // Map provider to Flutterwave currency code
        $currencyMap = [
            'mtn' => 'GHS',
            'vodafone' => 'GHS',
            'airteltigo' => 'GHS',
        ];

        $response = $this->getHttpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/charges?type=mobile_money_ghana', [
            'amount' => $amount,
            'currency' => $currencyMap[$provider] ?? 'GHS',
            'phone_number' => $phone,
            'network' => $this->mapProviderToFlutterwave($provider),
            'email' => auth()->user()->email ?? 'customer@example.com',
            'tx_ref' => $reference,
            'callback_url' => $callbackUrl,
            'redirect_url' => $callbackUrl,
        ]);

        $responseData = $response->json();

        // Check for authentication errors
        if ($response->status() === 401 || $response->status() === 403) {
            Log::error('Flutterwave Authentication Failed', [
                'status' => $response->status(),
                'response' => $responseData,
            ]);
            
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Invalid API key. Please check your Flutterwave Secret Key in the .env file. Make sure you are using the SECRET KEY, not the PUBLIC KEY.',
                'transaction_id' => null,
            ];
        }

        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment prompt sent to your mobile device. Please approve the payment.',
                'transaction_id' => $responseData['data']['id'] ?? $reference,
                'flw_ref' => $responseData['data']['flw_ref'] ?? null,
            ];
        }

        // Log the full response for debugging
        Log::warning('Flutterwave Payment Initiation Failed', [
            'status_code' => $response->status(),
            'response' => $responseData,
        ]);

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $responseData['message'] ?? 'Failed to initiate payment',
            'transaction_id' => null,
        ];
    }

    /**
     * Initiate payment via Paystack
     */
    private function initiatePaystackPayment($amount, $phone, $reference, $provider, $callbackUrl): array
    {
        // Paystack uses a different approach - create a transaction and then charge
        $response = $this->getHttpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->baseUrl . '/charge', [
            'amount' => $amount * 100, // Paystack uses kobo/pesewas
            'email' => auth()->user()->email ?? 'customer@example.com',
            'currency' => 'GHS',
            'reference' => $reference,
            'mobile_money' => [
                'phone' => $phone,
                'provider' => $this->mapProviderToPaystack($provider),
            ],
            'callback_url' => $callbackUrl,
        ]);

        $responseData = $response->json();

        if ($response->successful() && $responseData['status'] === true) {
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment prompt sent to your mobile device. Please approve the payment.',
                'transaction_id' => $responseData['data']['reference'] ?? $reference,
                'authorization_url' => $responseData['data']['authorization_url'] ?? null,
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $responseData['message'] ?? 'Failed to initiate payment',
            'transaction_id' => null,
        ];
    }

    /**
     * Initiate direct MoMo payment (if you have direct API access)
     * This is a placeholder - implement based on your MoMo provider's API
     */
    private function initiateDirectMomoPayment($amount, $phone, $reference, $provider, $callbackUrl): array
    {
        // This would be implemented based on the specific MoMo provider's API
        // For example, MTN Mobile Money API, Vodafone Cash API, etc.
        
        // Placeholder implementation
        return [
            'success' => false,
            'status' => 'failed',
            'message' => 'Direct MoMo API integration not configured',
            'transaction_id' => null,
        ];
    }

    /**
     * Verify payment status
     */
    public function verifyPayment(string $transactionId): array
    {
        try {
            switch ($this->provider) {
                case 'fincra':
                    return $this->verifyFincraPayment($transactionId);
                
                case 'flutterwave':
                    return $this->verifyFlutterwavePayment($transactionId);
                
                case 'paystack':
                    return $this->verifyPaystackPayment($transactionId);
                
                default:
                    return [
                        'success' => false,
                        'status' => 'unknown',
                        'message' => 'Payment verification not available for this provider',
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Payment Verification Failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify Fincra payment
     */
    private function verifyFincraPayment(string $transactionId): array
    {
        $response = $this->getHttpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->baseUrl . '/payment-links/' . $transactionId);

        $responseData = $response->json();

        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
            $transaction = $responseData['data'] ?? [];
            $status = $transaction['status'] ?? 'pending';

            return [
                'success' => true,
                'status' => $this->mapFincraStatus($status),
                'message' => $this->getStatusMessage($this->mapFincraStatus($status)),
                'amount' => $transaction['amount'] ?? null,
                'currency' => $transaction['currency'] ?? 'GHS',
                'transaction_id' => $transaction['id'] ?? $transactionId,
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $responseData['message'] ?? 'Payment verification failed',
        ];
    }

    /**
     * Map Fincra status to our status
     */
    private function mapFincraStatus(string $status): string
    {
        $map = [
            'completed' => 'completed',
            'paid' => 'completed',
            'success' => 'completed',
            'pending' => 'pending',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        return $map[strtolower($status)] ?? 'pending';
    }

    /**
     * Verify Flutterwave payment
     */
    private function verifyFlutterwavePayment(string $transactionId): array
    {
        $response = $this->getHttpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->baseUrl . '/transactions/' . $transactionId . '/verify');

        $responseData = $response->json();

        if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'success') {
            $transaction = $responseData['data'] ?? [];
            $status = $transaction['status'] ?? 'pending';

            return [
                'success' => true,
                'status' => $this->mapFlutterwaveStatus($status),
                'message' => $this->getStatusMessage($status),
                'amount' => $transaction['amount'] ?? null,
                'currency' => $transaction['currency'] ?? 'GHS',
                'transaction_id' => $transaction['id'] ?? $transactionId,
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $responseData['message'] ?? 'Payment verification failed',
        ];
    }

    /**
     * Verify Paystack payment
     */
    private function verifyPaystackPayment(string $reference): array
    {
        $response = $this->getHttpClient()->withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
        ])->get($this->baseUrl . '/transaction/verify/' . $reference);

        $responseData = $response->json();

        if ($response->successful() && $responseData['status'] === true) {
            $transaction = $responseData['data'] ?? [];
            $status = $transaction['status'] ?? 'pending';

            return [
                'success' => true,
                'status' => $this->mapPaystackStatus($status),
                'message' => $this->getStatusMessage($status),
                'amount' => ($transaction['amount'] ?? 0) / 100, // Convert from pesewas
                'currency' => $transaction['currency'] ?? 'GHS',
                'transaction_id' => $transaction['reference'] ?? $reference,
            ];
        }

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $responseData['message'] ?? 'Payment verification failed',
        ];
    }

    /**
     * Format phone number for payment gateway
     */
    private function formatPhoneNumber(string $phone, string $provider): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);

        // Ensure phone starts with country code
        if (!str_starts_with($phone, '233')) {
            // Remove leading 0 if present
            if (str_starts_with($phone, '0')) {
                $phone = substr($phone, 1);
            }
            $phone = '233' . $phone;
        }

        return $phone;
    }

    /**
     * Map provider to Flutterwave network code
     */
    private function mapProviderToFlutterwave(string $provider): string
    {
        $map = [
            'mtn' => 'MTN',
            'vodafone' => 'VODAFONE',
            'airteltigo' => 'AIRTELTIGO',
        ];

        return $map[$provider] ?? 'MTN';
    }

    /**
     * Map provider to Paystack network code
     */
    private function mapProviderToPaystack(string $provider): string
    {
        $map = [
            'mtn' => 'MTN',
            'vodafone' => 'VODAFONE',
            'airteltigo' => 'AIRTELTIGO',
        ];

        return $map[$provider] ?? 'MTN';
    }

    /**
     * Map Flutterwave status to our status
     */
    private function mapFlutterwaveStatus(string $status): string
    {
        $map = [
            'successful' => 'completed',
            'success' => 'completed',
            'pending' => 'pending',
            'failed' => 'failed',
            'cancelled' => 'cancelled',
        ];

        return $map[strtolower($status)] ?? 'pending';
    }

    /**
     * Map Paystack status to our status
     */
    private function mapPaystackStatus(string $status): string
    {
        $map = [
            'success' => 'completed',
            'pending' => 'pending',
            'failed' => 'failed',
            'reversed' => 'cancelled',
        ];

        return $map[strtolower($status)] ?? 'pending';
    }

    /**
     * Get status message
     */
    private function getStatusMessage(string $status): string
    {
        $messages = [
            'completed' => 'Payment completed successfully',
            'pending' => 'Payment is pending approval',
            'failed' => 'Payment failed',
            'cancelled' => 'Payment was cancelled',
        ];

        return $messages[$status] ?? 'Payment status unknown';
    }
}

