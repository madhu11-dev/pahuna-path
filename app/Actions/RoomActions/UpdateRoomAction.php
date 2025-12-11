<?php

namespace App\Actions\RoomActions;

use App\Models\Room;

class UpdateRoomAction
{
    public function handle(Room $room, array $data): Room
    {
        $room->update($data);
        return $room->fresh();
    }
}
