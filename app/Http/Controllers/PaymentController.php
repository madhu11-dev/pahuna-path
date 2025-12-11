<?php

namespace App\Http\Controllers;

use App\Http\Requests\VerifyPaymentRequest;
use App\Models\Booking;
use App\Services\TransactionService;
use Illuminate\Http\JsonResponse;

class PaymentController extends Controller
{
    public function __construct(protected TransactionService $transactionService) {}

    /**
     * Verify Khalti payment
     * Middleware: auth:sanctum
     */
    public function verifyPayment(VerifyPaymentRequest $request): JsonResponse
    {
        $booking = Booking::with(['user', 'accommodation', 'room'])->findOrFail($request->booking_id);

        // Authorization check
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if already paid
        if ($booking->isPaid()) {
            return response()->json([
                'status' => false,
                'message' => 'Booking is already paid'
            ], 400);
        }

        // Process payment
        $result = $this->transactionService->processPayment(
            $booking,
            $request->token,
            'khalti'
        );

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'],
            'booking' => $result['booking'],
            'transaction' => $result['transaction']
        ]);
    }

    /**
     * Initiate refund for cancelled booking
     * Middleware: auth:sanctum
     */
    public function initiateRefund($bookingId): JsonResponse
    {
        $booking = Booking::with(['user', 'accommodation', 'room'])->findOrFail($bookingId);

        // Authorization check
        if ($booking->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Check if can be cancelled
        if (!$booking->canBeCancelled()) {
            return response()->json([
                'status' => false,
                'message' => 'This booking cannot be cancelled'
            ], 400);
        }

        // Process refund
        $result = $this->transactionService->processRefund($booking);

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message']
            ], 400);
        }

        return response()->json([
            'status' => true,
            'message' => $result['message'],
            'refund_amount' => $result['refund_amount'],
            'booking' => $result['booking']
        ]);
    }

    /**
     * Get booking payment information
     * Middleware: auth:sanctum
     */
    public function getBookingPaymentInfo($bookingId): JsonResponse
    {
        $booking = Booking::with(['user', 'accommodation', 'room', 'transactions'])
            ->findOrFail($bookingId);

        // Authorization check
        if ($booking->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'booking' => $booking,
            'is_paid' => $booking->isPaid(),
            'can_be_cancelled' => $booking->canBeCancelled(),
            'refund_amount' => $booking->getRefundAmount(),
        ]);
    }
}

