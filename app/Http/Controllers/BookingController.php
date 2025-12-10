<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Room;
use App\Models\ExtraService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        if ($user->isStaff()) {
            $bookings = Booking::whereHas('accommodation', function($query) use ($user) {
                $query->where('staff_id', $user->id);
            })
            ->with(['user', 'room', 'accommodation', 'services.service'])
            ->latest()
            ->get();
        } else {
            $bookings = Booking::where('user_id', $user->id)
                ->with(['room', 'accommodation', 'services.service'])
                ->latest()
                ->get();
        }

        return response()->json([
            'status' => true,
            'data' => $bookings
        ]);
    }

    public function show($id)
    {
        $booking = Booking::with(['user', 'room', 'accommodation', 'services.service'])
            ->findOrFail($id);
        
        $user = request()->user();
        if ($booking->user_id !== $user->id && $booking->accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => $booking
        ]);
    }

    public function store(StoreBookingRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();
        
        $room = Room::findOrFail($data['room_id']);
        
        $checkInDate = Carbon::parse($data['check_in_date']);
        $checkOutDate = Carbon::parse($data['check_out_date']);
        $totalNights = $checkInDate->diffInDays($checkOutDate);
        
        $availableRooms = $room->getAvailableRooms($data['check_in_date'], $data['check_out_date']);
        
        if ($availableRooms < $data['number_of_rooms']) {
            return response()->json([
                'status' => false,
                'message' => 'Not enough rooms available. Only ' . $availableRooms . ' rooms left.'
            ], 400);
        }
        
        if ($data['number_of_guests'] > ($room->capacity * $data['number_of_rooms'])) {
            return response()->json([
                'status' => false,
                'message' => 'Guest count exceeds room capacity'
            ], 400);
        }
        
        $roomSubtotal = $room->base_price * $data['number_of_rooms'] * $totalNights;
        $servicesSubtotal = 0;
        
        $booking = Booking::create([
            'user_id' => $user->id,
            'accommodation_id' => $data['accommodation_id'],
            'room_id' => $data['room_id'],
            'check_in_date' => $data['check_in_date'],
            'check_out_date' => $data['check_out_date'],
            'number_of_rooms' => $data['number_of_rooms'],
            'number_of_guests' => $data['number_of_guests'],
            'total_nights' => $totalNights,
            'room_subtotal' => $roomSubtotal,
            'services_subtotal' => 0,
            'total_amount' => $roomSubtotal,
            'special_requests' => $data['special_requests'] ?? null,
        ]);
        
        if (!empty($data['services'])) {
            foreach ($data['services'] as $serviceData) {
                $service = ExtraService::findOrFail($serviceData['service_id']);
                $quantity = $serviceData['quantity'];
                $subtotal = $service->price * $quantity;
                
                $booking->services()->create([
                    'service_id' => $service->id,
                    'quantity' => $quantity,
                    'price' => $service->price,
                    'subtotal' => $subtotal,
                ]);
                
                $servicesSubtotal += $subtotal;
            }
            
            $booking->update([
                'services_subtotal' => $servicesSubtotal,
                'total_amount' => $roomSubtotal + $servicesSubtotal,
            ]);
        }
        
        $booking->load(['room', 'accommodation', 'services.service']);

        return response()->json([
            'status' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'booking_status' => 'required|in:confirmed,checked_in,checked_out,cancelled'
        ]);

        $booking = Booking::findOrFail($id);
        $user = $request->user();
        
        if (!$user->isStaff() || $booking->accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $booking->update([
            'booking_status' => $request->booking_status
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Booking status updated successfully',
            'data' => $booking
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $request->validate([
            'cancellation_reason' => 'nullable|string|max:2000'
        ]);

        $booking = Booking::findOrFail($id);
        $user = $request->user();
        
        if ($booking->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($booking->booking_status === 'cancelled') {
            return response()->json([
                'status' => false,
                'message' => 'Booking already cancelled'
            ], 400);
        }

        if (in_array($booking->booking_status, ['checked_in', 'checked_out'])) {
            return response()->json([
                'status' => false,
                'message' => 'Cannot cancel booking that is already checked in or completed'
            ], 400);
        }

        // Update booking status
        $booking->update([
            'booking_status' => 'cancelled',
            'cancellation_reason' => $request->cancellation_reason,
            'cancelled_at' => now(),
        ]);

        // Process refund if booking is paid
        $refundAmount = 0;
        $refundMessage = '';
        if ($booking->isPaid()) {
            $refundAmount = $booking->getRefundAmount();
            
            if ($refundAmount > 0) {
                try {
                    $transactionService = app(\App\Services\TransactionService::class);
                    $refundResult = $transactionService->processRefund($booking);
                    
                    if ($refundResult['success']) {
                        $refundMessage = "Refund of Rs. {$refundAmount} (80% of booking amount) has been processed.";
                    } else {
                        // Log the error but don't fail the cancellation
                        \Log::error('Refund processing failed during cancellation', [
                            'booking_id' => $booking->id,
                            'error' => $refundResult['message']
                        ]);
                        $refundMessage = "Booking cancelled. Refund processing is pending.";
                    }
                } catch (\Exception $e) {
                    \Log::error('Refund processing exception during cancellation', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage()
                    ]);
                    $refundMessage = "Booking cancelled. Refund processing is pending.";
                }
            } else {
                $refundMessage = "No refund applicable (cancellation is within 2 days of check-in).";
            }
        }

        $booking->load(['room', 'accommodation', 'services.service']);

        return response()->json([
            'status' => true,
            'message' => $refundAmount > 0 
                ? "Booking cancelled successfully. {$refundMessage}"
                : 'Booking cancelled successfully',
            'data' => $booking,
            'refund_amount' => $refundAmount,
            'refund_message' => $refundMessage
        ]);
    }
}
