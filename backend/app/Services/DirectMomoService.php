<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DirectMomoService
{
    private $mtnApiKey;
    private $mtnApiSecret;
    private $mtnMerchantId;
    private $vodafoneApiKey;
    private $vodafoneApiSecret;
    private $airteltigoApiKey;
    private $airteltigoApiSecret;

    public function __construct()
    {
        // Direct Mobile Money API credentials from environment
        $this->mtnApiKey = config('services.momo.mtn_api_key');
        $this->mtnApiSecret = config('services.momo.mtn_api_secret');
        $this->mtnMerchantId = config('services.momo.mtn_merchant_id');
        
        $this->vodafoneApiKey = config('services.momo.vodafone_api_key');
        $this->vodafoneApiSecret = config('services.momo.vodafone_api_secret');
        
        $this->airteltigoApiKey = config('services.momo.airteltigo_api_key');
        $this->airteltigoApiSecret = config('services.momo.airteltigo_api_secret');
    }

    /**
     * Initiate direct mobile money payment
     * 
     * @param array $data Payment data (amount, phone, provider, reference)
     * @return array Response with transaction_id, status, and message
     */
    public function initiatePayment(array $data): array
    {
        $provider = strtolower($data['provider']); // mtn, vodafone, airteltigo
        $amount = $data['amount'];
        $phone = $this->formatPhoneNumber($data['phone']);
        $reference = $data['reference'];

        try {
            switch ($provider) {
                case 'mtn':
                    return $this->initiateMTNPayment($amount, $phone, $reference);
                
                case 'vodafone':
                    return $this->initiateVodafonePayment($amount, $phone, $reference);
                
                case 'airteltigo':
                    return $this->initiateAirtelTigoPayment($amount, $phone, $reference);
                
                default:
                    throw new \Exception("Unsupported mobile money provider: {$provider}");
            }
        } catch (\Exception $e) {
            Log::error('Direct MoMo Payment Initiation Failed', [
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
     * Initiate MTN Mobile Money payment
     */
    private function initiateMTNPayment($amount, $phone, $reference): array
    {
        // Check if MTN API credentials are configured
        if (empty($this->mtnApiKey) || empty($this->mtnApiSecret)) {
            // Fallback: Create payment record and return instructions
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment request created. Please dial *170# and follow the prompts to complete payment.',
                'transaction_id' => $reference,
                'instructions' => "Dial *170# on your MTN Mobile Money number ({$phone}) and follow the prompts to pay GHS {$amount}",
            ];
        }

        // If credentials are available, use MTN API
        // Note: This requires MTN Merchant API access
        try {
            $response = $this->getHttpClient()->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->mtnApiKey . ':' . $this->mtnApiSecret),
                'Content-Type' => 'application/json',
            ])->post('https://api.momoapi.mtn.com/v1/requesttopay', [
                'amount' => $amount,
                'currency' => 'GHS',
                'externalId' => $reference,
                'payer' => [
                    'partyIdType' => 'MSISDN',
                    'partyId' => $phone,
                ],
                'payerMessage' => 'Subscription Payment',
                'payeeNote' => 'Payment for ' . $reference,
            ]);

            $responseData = $response->json();

            if ($response->successful() && isset($responseData['status']) && $responseData['status'] === 'PENDING') {
                return [
                    'success' => true,
                    'status' => 'pending',
                    'message' => 'Payment request sent. Please approve on your mobile device.',
                    'transaction_id' => $responseData['transactionId'] ?? $reference,
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'message' => $responseData['message'] ?? 'Failed to initiate MTN payment',
                'transaction_id' => null,
            ];
        } catch (\Exception $e) {
            // Fallback to USSD instructions
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment request created. Please dial *170# to complete payment.',
                'transaction_id' => $reference,
                'instructions' => "Dial *170# on your MTN Mobile Money number ({$phone}) and follow the prompts to pay GHS {$amount}",
            ];
        }
    }

    /**
     * Initiate Vodafone Cash payment
     */
    private function initiateVodafonePayment($amount, $phone, $reference): array
    {
        // Vodafone Cash API integration
        // For now, return USSD instructions
        return [
            'success' => true,
            'status' => 'pending',
            'message' => 'Payment request created. Please dial *110# to complete payment.',
            'transaction_id' => $reference,
            'instructions' => "Dial *110# on your Vodafone Cash number ({$phone}) and follow the prompts to pay GHS {$amount}",
        ];
    }

    /**
     * Initiate AirtelTigo Money payment
     */
    private function initiateAirtelTigoPayment($amount, $phone, $reference): array
    {
        // AirtelTigo Money API integration
        // For now, return USSD instructions
        return [
            'success' => true,
            'status' => 'pending',
            'message' => 'Payment request created. Please dial *110# to complete payment.',
            'transaction_id' => $reference,
            'instructions' => "Dial *110# on your AirtelTigo Money number ({$phone}) and follow the prompts to pay GHS {$amount}",
        ];
    }

    /**
     * Verify payment status by checking transaction
     */
    public function verifyPayment(string $transactionId, string $provider): array
    {
        try {
            switch (strtolower($provider)) {
                case 'mtn':
                    return $this->verifyMTNPayment($transactionId);
                
                case 'vodafone':
                    return $this->verifyVodafonePayment($transactionId);
                
                case 'airteltigo':
                    return $this->verifyAirtelTigoPayment($transactionId);
                
                default:
                    return [
                        'success' => false,
                        'status' => 'unknown',
                        'message' => 'Payment verification not available for this provider',
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Direct MoMo Payment Verification Failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'provider' => $provider,
            ]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Failed to verify payment: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify MTN payment
     */
    private function verifyMTNPayment(string $transactionId): array
    {
        if (empty($this->mtnApiKey) || empty($this->mtnApiSecret)) {
            // Without API access, we can't verify automatically
            // Return pending status - manual verification required
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment verification pending. Please check your mobile money account.',
            ];
        }

        try {
            $response = $this->getHttpClient()->withHeaders([
                'Authorization' => 'Basic ' . base64_encode($this->mtnApiKey . ':' . $this->mtnApiSecret),
            ])->get("https://api.momoapi.mtn.com/v1/requesttopay/{$transactionId}");

            $responseData = $response->json();

            if ($response->successful()) {
                $status = $responseData['status'] ?? 'PENDING';
                
                return [
                    'success' => true,
                    'status' => $this->mapMTNStatus($status),
                    'message' => $this->getStatusMessage($this->mapMTNStatus($status)),
                    'amount' => $responseData['amount'] ?? null,
                    'currency' => $responseData['currency'] ?? 'GHS',
                    'transaction_id' => $transactionId,
                ];
            }

            return [
                'success' => false,
                'status' => 'failed',
                'message' => $responseData['message'] ?? 'Payment verification failed',
            ];
        } catch (\Exception $e) {
            return [
                'success' => true,
                'status' => 'pending',
                'message' => 'Payment verification pending. Please check your mobile money account.',
            ];
        }
    }

    /**
     * Verify Vodafone payment
     */
    private function verifyVodafonePayment(string $transactionId): array
    {
        // Similar to MTN - implement based on Vodafone API
        return [
            'success' => true,
            'status' => 'pending',
            'message' => 'Payment verification pending. Please check your mobile money account.',
        ];
    }

    /**
     * Verify AirtelTigo payment
     */
    private function verifyAirtelTigoPayment(string $transactionId): array
    {
        // Similar to MTN - implement based on AirtelTigo API
        return [
            'success' => true,
            'status' => 'pending',
            'message' => 'Payment verification pending. Please check your mobile money account.',
        ];
    }

    /**
     * Format phone number for Ghana (233XXXXXXXXX)
     */
    private function formatPhoneNumber(string $phone): string
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
     * Map MTN status to our status
     */
    private function mapMTNStatus(string $status): string
    {
        $map = [
            'SUCCESSFUL' => 'completed',
            'SUCCESS' => 'completed',
            'PENDING' => 'pending',
            'FAILED' => 'failed',
            'CANCELLED' => 'cancelled',
        ];

        return $map[strtoupper($status)] ?? 'pending';
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
        
        if (!$verify) {
            return Http::withoutVerifying();
        }
        
        return Http::withOptions([
            'verify' => true,
        ]);
    }
}

