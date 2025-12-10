<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class KhaltiService
{
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.khalti.secret_key');
        $this->baseUrl = config('services.khalti.base_url');
    }

    /**
     * Verify payment with Khalti
     */
    public function verifyPayment(string $token, int $amount): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->secretKey,
            ])->post($this->baseUrl . '/payment/verify/', [
                'token' => $token,
                'amount' => $amount,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Khalti payment verified', [
                    'token' => $token,
                    'amount' => $amount,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            Log::error('Khalti payment verification failed', [
                'token' => $token,
                'amount' => $amount,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification failed',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Khalti payment verification exception', [
                'token' => $token,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment verification error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Initiate refund with Khalti
     */
    public function initiateRefund(string $transactionId, int $amount): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Key ' . $this->secretKey,
            ])->post($this->baseUrl . '/payment/refund/', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Khalti refund initiated', [
                    'transaction_id' => $transactionId,
                    'amount' => $amount,
                    'response' => $data
                ]);

                return [
                    'success' => true,
                    'data' => $data
                ];
            }

            Log::error('Khalti refund initiation failed', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'status' => $response->status(),
                'response' => $response->body()
            ]);

            return [
                'success' => false,
                'message' => 'Refund initiation failed',
                'error' => $response->json()
            ];

        } catch (\Exception $e) {
            Log::error('Khalti refund initiation exception', [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Refund initiation error: ' . $e->getMessage()
            ];
        }
    }
}
