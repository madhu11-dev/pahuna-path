<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(protected BookingService $bookingService) {}

    /**
     * Get user's or staff's bookings
     * Middleware: auth:sanctum
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isStaff()) {
            $bookings = Booking::whereHas('accommodation', function ($query) use ($user) {
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
            'data' => BookingResource::collection($bookings)
        ]);
    }

    /**
     * Show single booking
     * Middleware: auth:sanctum
     */
    public function show(Request $request, $id): JsonResponse
    {
        $booking = Booking::with(['user', 'room', 'accommodation', 'services.service'])
            ->findOrFail($id);

        $user = $request->user();

        // Authorization: user owns booking or staff owns accommodation
        if ($booking->user_id !== $user->id && $booking->accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'data' => new BookingResource($booking)
        ]);
    }

    /**
     * Create new booking
     * Middleware: auth:sanctum
     */
    public function store(StoreBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        $room = Room::findOrFail($data['room_id']);

        // Check room availability
        $availabilityCheck = $this->bookingService->checkRoomAvailability(
            $room,
            $data['check_in_date'],
            $data['check_out_date'],
            $data['number_of_rooms']
        );

        if (!$availabilityCheck['available']) {
            return response()->json([
                'status' => false,
                'message' => 'Not enough rooms available. Only ' . $availabilityCheck['available_rooms'] . ' rooms left.'
            ], 400);
        }

        // Check guest capacity
        if ($data['number_of_guests'] > ($room->capacity * $data['number_of_rooms'])) {
            return response()->json([
                'status' => false,
                'message' => 'Guest count exceeds room capacity'
            ], 400);
        }

        // Create booking
        $booking = $this->bookingService->createBooking($data, $request->user()->id);

        return response()->json([
            'status' => true,
            'message' => 'Booking created successfully',
            'data' => new BookingResource($booking->load(['room', 'accommodation', 'services.service']))
        ], 201);
    }

    /**
     * Update booking status - Staff only
     * Middleware: auth.staff
     */
    public function updateStatus(Request $request, $id): JsonResponse
    {
        $booking = Booking::with('accommodation')->findOrFail($id);

        // Authorization: only accommodation owner can update status
        if ($booking->accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'booking_status' => 'required|in:pending,confirmed,cancelled,completed'
        ]);

        $result = $this->bookingService->updateBookingStatus($booking, $request->booking_status);
        
        $updatedBooking = $result['booking'];
        $refundAmount = $result['refund_amount'] ?? 0;

        $response = [
            'status' => true,
            'message' => 'Booking status updated successfully',
            'data' => new BookingResource($updatedBooking->load(['user', 'room', 'accommodation', 'services.service']))
        ];
        
        if ($refundAmount > 0) {
            $response['refund_amount'] = $refundAmount;
            $response['message'] = 'Booking cancelled successfully. Refund will be processed.';
        }

        return response()->json($response);
    }

    /**
     * Cancel booking - User only
     * Middleware: auth:sanctum
     */
    public function cancel(Request $request, $id): JsonResponse
    {
        $booking = Booking::findOrFail($id);

        // Authorization: only booking owner can cancel
        if ($booking->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!$booking->canBeCancelled()) {
            return response()->json([
                'status' => false,
                'message' => 'This booking cannot be cancelled'
            ], 400);
        }

        $booking = $this->bookingService->cancelBooking($booking);

        return response()->json([
            'status' => true,
            'message' => 'Booking cancelled successfully',
            'data' => new BookingResource($booking->load(['room', 'accommodation', 'services.service']))
        ]);
    }
}
