<?php

namespace App\Actions\PaymentActions;

use App\Models\Booking;
use App\Services\KhaltiService;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class VerifyPaymentAction
{
    public function __construct(
        protected KhaltiService $khaltiService,
        protected TransactionService $transactionService
    ) {}

    public function handle(Booking $booking, string $token, string $paymentMethod = 'khalti'): array
    {
        // Check if already paid
        if ($booking->isPaid()) {
            return [
                'success' => false,
                'message' => 'Booking is already paid'
            ];
        }

        // Process payment
        $result = $this->transactionService->processPayment($booking, $token, $paymentMethod);

        return $result;
    }
}
