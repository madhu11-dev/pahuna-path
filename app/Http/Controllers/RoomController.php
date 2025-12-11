<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckRoomAvailabilityRequest;
use App\Http\Requests\StoreRoomRequest;
use App\Http\Requests\UpdateRoomRequest;
use App\Http\Resources\RoomResource;
use App\Models\Accommodation;
use App\Models\Room;
use App\Services\RoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    public function __construct(protected RoomService $roomService) {}

    /**
     * Get all rooms for an accommodation
     */
    public function index($accommodationId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $rooms = $accommodation->rooms()->get();

        return response()->json([
            'status' => true,
            'data' => RoomResource::collection($rooms)
        ]);
    }

    /**
     * Create new room - Owner staff only
     * Middleware: auth.staff
     */
    public function store(StoreRoomRequest $request, $accommodationId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);

        // Authorization: only owner can create rooms
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();
        $room = $this->roomService->createRoom($accommodation, $data);

        return response()->json([
            'status' => true,
            'message' => 'Room created successfully',
            'data' => new RoomResource($room)
        ], 201);
    }

    /**
     * Update room - Owner staff only
     * Middleware: auth.staff
     */
    public function update(UpdateRoomRequest $request, $accommodationId, $roomId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($roomId);

        // Authorization: only owner can update rooms
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();
        $room = $this->roomService->updateRoom($room, $data);

        return response()->json([
            'status' => true,
            'message' => 'Room updated successfully',
            'data' => new RoomResource($room)
        ]);
    }

    /**
     * Delete room - Owner staff only
     * Middleware: auth.staff
     */
    public function destroy(Request $request, $accommodationId, $roomId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($roomId);

        // Authorization: only owner can delete rooms
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $this->roomService->deleteRoom($room);

        return response()->json([
            'status' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    /**
     * Check room availability
     */
    public function checkAvailability(CheckRoomAvailabilityRequest $request, $accommodationId, $roomId): JsonResponse
    {
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($roomId);

        $availableRooms = $room->getAvailableRooms(
            $request->check_in_date,
            $request->check_out_date
        );

        return response()->json([
            'status' => true,
            'data' => [
                'available_rooms' => $availableRooms,
                'total_rooms' => $room->total_rooms
            ]
        ]);
    }
}

