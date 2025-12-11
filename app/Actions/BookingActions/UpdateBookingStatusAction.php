<?php

namespace App\Actions\BookingActions;

use App\Models\Booking;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Log;

class UpdateBookingStatusAction
{
    public function __construct(protected TransactionService $transactionService) {}

    public function handle(Booking $booking, string $status): array
    {
        $refundAmount = 0;
        
        // If staff is cancelling a paid booking, process 80% refund automatically
        if ($status === 'cancelled' && $booking->isPaid()) {
            // Check if booking is at least 2 days before check-in for refund eligibility
            $daysUntilCheckIn = now()->diffInDays($booking->check_in_date, false);
            
            if ($daysUntilCheckIn >= 2) {
                try {
                    $refundResult = $this->transactionService->processRefund($booking);
                    
                    if ($refundResult['success']) {
                        $refundAmount = $refundResult['refund_amount'] ?? 0;
                    } else {
                        Log::warning('Refund processing failed during staff cancellation', [
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
                // Still update booking to cancelled, but no refund
                $booking->update(['booking_status' => $status]);
            }
        } else {
            // For other status updates, just update the status
            $booking->update(['booking_status' => $status]);
        }
        
        return [
            'booking' => $booking->fresh(),
            'refund_amount' => $refundAmount
        ];
    }
}
