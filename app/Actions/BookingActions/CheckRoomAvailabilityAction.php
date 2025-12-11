<?php

namespace App\Actions\BookingActions;

use App\Models\Room;
use Carbon\Carbon;

class CheckRoomAvailabilityAction
{
    public function handle(Room $room, string $checkInDate, string $checkOutDate, int $numberOfRooms): array
    {
        $availableRooms = $room->getAvailableRooms($checkInDate, $checkOutDate);
        $isAvailable = $availableRooms >= $numberOfRooms;

        return [
            'available' => $isAvailable,
            'available_rooms' => $availableRooms,
            'requested_rooms' => $numberOfRooms,
        ];
    }
}
