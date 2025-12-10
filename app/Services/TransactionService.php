<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Transaction;
use App\Mail\BookingConfirmedMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class TransactionService
{
    protected KhaltiService $khaltiService;

    public function __construct(KhaltiService $khaltiService)
    {
        $this->khaltiService = $khaltiService;
    }

    /**
     * Process payment for a booking
     */
    public function processPayment(Booking $booking, string $token, string $paymentMethod = 'khalti'): array
    {
        DB::beginTransaction();
        
        try {
            // Convert amount to paisa for Khalti (1 NPR = 100 paisa)
            $amountInPaisa = (int)($booking->total_amount * 100);

            // Verify payment with Khalti
            $verification = $this->khaltiService->verifyPayment($token, $amountInPaisa);

            if (!$verification['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $verification['message'] ?? 'Payment verification failed'
                ];
            }

            $paymentData = $verification['data'];

            // Create transaction record
            $transaction = Transaction::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'transaction_id' => $paymentData['idx'] ?? $token,
                'transaction_type' => 'payment',
                'amount' => $booking->total_amount,
                'status' => 'completed',
                'payment_method' => $paymentMethod,
                'payment_response' => $paymentData,
            ]);

            // Update booking
            $booking->update([
                'payment_status' => 'paid',
                'booking_status' => 'confirmed',
                'payment_method' => $paymentMethod,
                'khalti_transaction_id' => $transaction->transaction_id,
                'payment_verified_at' => now(),
            ]);

            DB::commit();

            // Send confirmation email
            try {
                Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
                
                // Send email to accommodation staff
                if ($booking->accommodation && $booking->accommodation->email) {
                    Mail::to($booking->accommodation->email)->send(new BookingConfirmedMail($booking));
                }
            } catch (\Exception $e) {
                Log::error('Failed to send booking confirmation email', [
                    'booking_id' => $booking->id,
                    'error' => $e->getMessage()
                ]);
            }

            Log::info('Payment processed successfully', [
                'booking_id' => $booking->id,
                'transaction_id' => $transaction->transaction_id,
                'amount' => $booking->total_amount
            ]);

            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'transaction' => $transaction,
                'booking' => $booking->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Payment processing failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Process refund for a booking
     */
    public function processRefund(Booking $booking): array
    {
        DB::beginTransaction();
        
        try {
            // Check if booking is paid
            if (!$booking->isPaid()) {
                return [
                    'success' => false,
                    'message' => 'Booking is not paid'
                ];
            }

            // Calculate refund amount
            $refundAmount = $booking->getRefundAmount();

            if ($refundAmount <= 0) {
                return [
                    'success' => false,
                    'message' => 'No refund available for this booking'
                ];
            }

            // Get original transaction
            $originalTransaction = $booking->transactions()
                ->where('transaction_type', 'payment')
                ->where('status', 'completed')
                ->first();

            if (!$originalTransaction) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Original transaction not found'
                ];
            }

            // Convert amount to paisa for Khalti
            $refundAmountInPaisa = (int)($refundAmount * 100);

            // Initiate refund with Khalti
            $refundResult = $this->khaltiService->initiateRefund(
                $originalTransaction->transaction_id,
                $refundAmountInPaisa
            );

            if (!$refundResult['success']) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => $refundResult['message'] ?? 'Refund initiation failed'
                ];
            }

            $refundData = $refundResult['data'];

            // Create refund transaction
            $refundTransaction = Transaction::create([
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'transaction_id' => $refundData['idx'] ?? 'refund_' . time(),
                'transaction_type' => 'refund',
                'amount' => $refundAmount,
                'status' => 'completed',
                'payment_method' => $booking->payment_method,
                'payment_response' => $refundData,
                'refund_id' => $originalTransaction->transaction_id,
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
            ]);

            // Update original transaction
            $originalTransaction->update([
                'refund_id' => $refundTransaction->transaction_id,
                'refund_amount' => $refundAmount,
                'refunded_at' => now(),
            ]);

            // Update booking
            $booking->update([
                'booking_status' => 'cancelled',
                'payment_status' => 'refunded',
                'cancelled_at' => now(),
            ]);

            DB::commit();

            Log::info('Refund processed successfully', [
                'booking_id' => $booking->id,
                'refund_transaction_id' => $refundTransaction->transaction_id,
                'refund_amount' => $refundAmount
            ]);

            return [
                'success' => true,
                'message' => 'Refund processed successfully',
                'refund_amount' => $refundAmount,
                'transaction' => $refundTransaction,
                'booking' => $booking->fresh()
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Refund processing failed', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'Refund processing failed: ' . $e->getMessage()
            ];
        }
    }
}
