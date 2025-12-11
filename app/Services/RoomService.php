<?php

namespace App\Services;

use App\Actions\RoomActions\CreateRoomAction;
use App\Actions\RoomActions\UpdateRoomAction;
use App\Actions\RoomActions\DeleteRoomAction;
use App\Models\Accommodation;
use App\Models\Room;
use Illuminate\Support\Facades\Storage;

class RoomService
{
    public function __construct(
        protected CreateRoomAction $createRoomAction,
        protected UpdateRoomAction $updateRoomAction,
        protected DeleteRoomAction $deleteRoomAction
    ) {}

    public function createRoom(Accommodation $accommodation, array $data): Room
    {
        // Handle image uploads
        if (isset($data['images']) && request()->hasFile('images')) {
            $imagePaths = [];
            foreach (request()->file('images') as $image) {
                $path = $image->store('rooms', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        return $this->createRoomAction->handle($data, $accommodation->id);
    }

    public function updateRoom(Room $room, array $data): Room
    {
        // Handle image uploads
        if (isset($data['images']) && request()->hasFile('images')) {
            // Delete old images
            if ($room->images) {
                foreach ($room->images as $oldImage) {
                    Storage::disk('public')->delete($oldImage);
                }
            }
            
            $imagePaths = [];
            foreach (request()->file('images') as $image) {
                $path = $image->store('rooms', 'public');
                $imagePaths[] = $path;
            }
            $data['images'] = $imagePaths;
        }

        return $this->updateRoomAction->handle($room, $data);
    }

    public function deleteRoom(Room $room): void
    {
        // Delete images
        if ($room->images) {
            foreach ($room->images as $image) {
                Storage::disk('public')->delete($image);
            }
        }

        $this->deleteRoomAction->handle($room);
    }
}

