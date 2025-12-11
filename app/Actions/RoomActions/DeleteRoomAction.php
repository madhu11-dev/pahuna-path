<?php

namespace App\Actions\RoomActions;

use App\Models\Room;

class DeleteRoomAction
{
    public function handle(Room $room): void
    {
        $room->delete();
    }
}
