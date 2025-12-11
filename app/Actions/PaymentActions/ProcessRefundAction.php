<?php

namespace App\Actions\PaymentActions;

use App\Models\Booking;
use App\Services\TransactionService;

class ProcessRefundAction
{
    public function __construct(protected TransactionService $transactionService) {}

    public function handle(Booking $booking): array
    {
        // Check if can be cancelled
        if (!$booking->canBeCancelled()) {
            return [
                'success' => false,
                'message' => 'This booking cannot be cancelled',
                'refund_amount' => 0
            ];
        }

        // Process refund
        return $this->transactionService->processRefund($booking);
    }
}
