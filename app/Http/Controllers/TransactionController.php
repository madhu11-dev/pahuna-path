<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TransactionController extends Controller
{
    /**
     * Get user's transactions
     */
    public function getUserTransactions(Request $request)
    {
        try {
            $transactions = Transaction::with(['booking.accommodation', 'booking.room'])
                ->where('user_id', $request->user()->id)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json($transactions, 200);
        } catch (\Exception $e) {
            Log::error('Get user transactions error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve transactions'
            ], 500);
        }
    }

    /**
     * Get all transactions for staff (requires staff role)
     */
    public function getStaffTransactions(Request $request)
    {
        try {
            // Check if user is staff
            if (!$request->user()->isStaff()) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            $staffId = $request->user()->id;

            $query = Transaction::with(['booking.accommodation', 'booking.room', 'user'])
                ->whereHas('booking.accommodation', function ($q) use ($staffId) {
                    $q->where('staff_id', $staffId);
                });

            // Filter by transaction type
            if ($request->has('transaction_type')) {
                $query->where('transaction_type', $request->transaction_type);
            }

            // Filter by status
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            // Filter by payment method
            if ($request->has('payment_method')) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by date range
            if ($request->has('start_date')) {
                $query->whereDate('created_at', '>=', $request->start_date);
            }

            if ($request->has('end_date')) {
                $query->whereDate('created_at', '<=', $request->end_date);
            }

            // Search by transaction ID or booking reference
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('transaction_id', 'like', "%{$search}%")
                        ->orWhereHas('booking', function ($q2) use ($search) {
                            $q2->where('booking_reference', 'like', "%{$search}%");
                        });
                });
            }

            $transactions = $query->orderBy('created_at', 'desc')->paginate(20);

            // Calculate statistics - only for staff's accommodations
            $stats = [
                'total_payments' => Transaction::where('transaction_type', 'payment')
                    ->where('status', 'completed')
                    ->whereHas('booking.accommodation', function ($q) use ($staffId) {
                        $q->where('staff_id', $staffId);
                    })
                    ->sum('amount'),
                'total_refunds' => Transaction::where('transaction_type', 'refund')
                    ->where('status', 'completed')
                    ->whereHas('booking.accommodation', function ($q) use ($staffId) {
                        $q->where('staff_id', $staffId);
                    })
                    ->sum('amount'),
                'pending_payments' => Transaction::where('transaction_type', 'payment')
                    ->where('status', 'pending')
                    ->whereHas('booking.accommodation', function ($q) use ($staffId) {
                        $q->where('staff_id', $staffId);
                    })
                    ->count(),
            ];

            return response()->json([
                'transactions' => $transactions,
                'stats' => $stats
            ], 200);
        } catch (\Exception $e) {
            Log::error('Get staff transactions error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve transactions'
            ], 500);
        }
    }

    /**
     * Get transaction details
     */
    public function getTransactionDetails(Request $request, $transactionId)
    {
        try {
            $transaction = Transaction::with(['booking.accommodation', 'booking.room', 'user'])
                ->findOrFail($transactionId);

            // Authorization check
            $user = $request->user();
            if (!$user->isStaff() && $transaction->user_id !== $user->id) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 403);
            }

            return response()->json($transaction, 200);
        } catch (\Exception $e) {
            Log::error('Get transaction details error', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Failed to retrieve transaction details'
            ], 500);
        }
    }
}
