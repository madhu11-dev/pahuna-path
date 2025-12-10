<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoomRequest;
use App\Models\Accommodation;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RoomController extends Controller
{
    public function index($accommodationId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $rooms = $accommodation->rooms()->get();

        return response()->json([
            'status' => true,
            'data' => $rooms
        ]);
    }

    public function store(StoreRoomRequest $request, $accommodationId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        
        $user = $request->user();
        if (!$user || !$user->isStaff() || $accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();
        
        if ($request->hasFile('images')) {
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('rooms', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $room = $accommodation->rooms()->create($data);

        return response()->json([
            'status' => true,
            'message' => 'Room created successfully',
            'data' => $room
        ], 201);
    }

    public function update(StoreRoomRequest $request, $accommodationId, $roomId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($roomId);
        
        $user = $request->user();
        if (!$user || !$user->isStaff() || $accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();
        
        if ($request->hasFile('images')) {
            if ($room->images) {
                foreach ($room->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            
            $imagePaths = [];
            foreach ($request->file('images') as $image) {
                $path = $image->store('rooms', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        $room->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Room updated successfully',
            'data' => $room
        ]);
    }

    public function destroy($accommodationId, $roomId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $room = Room::where('accommodation_id', $accommodationId)->findOrFail($roomId);
        
        $user = request()->user();
        if (!$user || !$user->isStaff() || $accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if ($room->images) {
            foreach ($room->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $room->delete();

        return response()->json([
            'status' => true,
            'message' => 'Room deleted successfully'
        ]);
    }

    public function checkAvailability(Request $request, $accommodationId, $roomId)
    {
        $request->validate([
            'check_in_date' => 'required|date|after_or_equal:today',
            'check_out_date' => 'required|date|after:check_in_date',
        ]);

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
