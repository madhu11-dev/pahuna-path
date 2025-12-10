<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    /**
     * Verify Khalti payment
     */
    public function verifyPayment(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'booking_id' => 'required|exists:bookings,id',
        ]);

        try {
            $booking = Booking::with(['user', 'accommodation', 'room'])->findOrFail($request->booking_id);

            // Authorization check
            if ($booking->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if already paid
            if ($booking->isPaid()) {
                return response()->json([
                    'message' => 'Booking is already paid'
                ], 400);
            }

            // Process payment
            $result = $this->transactionService->processPayment(
                $booking,
                $request->token,
                'khalti'
            );

            if ($result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'booking' => $result['booking'],
                    'transaction' => $result['transaction']
                ], 200);
            }

            return response()->json([
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Payment verification endpoint error', [
                'booking_id' => $request->booking_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Payment verification failed'
            ], 500);
        }
    }

    /**
     * Initiate refund for cancelled booking
     */
    public function initiateRefund(Request $request, $bookingId)
    {
        try {
            $booking = Booking::with(['user', 'accommodation', 'room'])->findOrFail($bookingId);

            // Authorization check
            if ($booking->user_id !== $request->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Check if can be cancelled
            if (!$booking->canBeCancelled()) {
                return response()->json([
                    'message' => 'This booking cannot be cancelled'
                ], 400);
            }

            // Process refund
            $result = $this->transactionService->processRefund($booking);

            if ($result['success']) {
                return response()->json([
                    'message' => $result['message'],
                    'refund_amount' => $result['refund_amount'],
                    'booking' => $result['booking']
                ], 200);
            }

            return response()->json([
                'message' => $result['message']
            ], 400);

        } catch (\Exception $e) {
            Log::error('Refund initiation endpoint error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Refund initiation failed'
            ], 500);
        }
    }

    /**
     * Get booking payment information
     */
    public function getBookingPaymentInfo($bookingId)
    {
        try {
            $booking = Booking::with(['user', 'accommodation', 'room', 'transactions'])
                ->findOrFail($bookingId);

            // Authorization check
            if ($booking->user_id !== request()->user()->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json([
                'booking' => $booking,
                'is_paid' => $booking->isPaid(),
                'can_be_cancelled' => $booking->canBeCancelled(),
                'refund_amount' => $booking->getRefundAmount(),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get booking payment info error', [
                'booking_id' => $bookingId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve booking information'
            ], 500);
        }
    }
}
