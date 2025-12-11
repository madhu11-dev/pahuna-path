<?php

namespace App\Actions\RoomActions;

use App\Models\Room;

class CreateRoomAction
{
    public function handle(array $data, int $accommodationId): Room
    {
        $data['accommodation_id'] = $accommodationId;
        return Room::create($data);
    }
}
