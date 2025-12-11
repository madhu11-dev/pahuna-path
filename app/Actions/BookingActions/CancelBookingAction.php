<?php

namespace App\Actions\BookingActions;

use App\Models\Booking;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class CancelBookingAction
{
    public function __construct(protected TransactionService $transactionService) {}

    public function handle(Booking $booking, ?string $cancellationReason = null): Booking
    {
        // If booking is paid, process refund
        if ($booking->isPaid()) {
            try {
                $refundResult = $this->transactionService->processRefund($booking);
                
                if (!$refundResult['success']) {
                    Log::warning('Refund processing failed during user cancellation', [
                        'booking_id' => $booking->id,
                        'error' => $refundResult['message']
                    ]);
                }
            } catch (\Exception $e) {
                Log::error('Exception during refund processing', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }
        } else {
            // If not paid, just update status
            $booking->update([
                'booking_status' => 'cancelled',
                'cancellation_reason' => $cancellationReason,
                'cancelled_at' => now(),
            ]);
        }

        return $booking->fresh();
    }
}
