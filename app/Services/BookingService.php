<?php

namespace App\Services;

use App\Actions\BookingActions\CreateBookingAction;
use App\Actions\BookingActions\CancelBookingAction;
use App\Actions\BookingActions\UpdateBookingStatusAction;
use App\Actions\BookingActions\CheckRoomAvailabilityAction;
use App\Models\Booking;
use App\Models\Room;

class BookingService
{
    public function __construct(
        protected CreateBookingAction $createBookingAction,
        protected CancelBookingAction $cancelBookingAction,
        protected UpdateBookingStatusAction $updateBookingStatusAction,
        protected CheckRoomAvailabilityAction $checkRoomAvailabilityAction
    ) {}

    public function createBooking(array $data, int $userId): Booking
    {
        return $this->createBookingAction->handle($data, $userId);
    }

    public function cancelBooking(Booking $booking, ?string $cancellationReason = null): Booking
    {
        return $this->cancelBookingAction->handle($booking, $cancellationReason);
    }

    public function updateBookingStatus(Booking $booking, string $status): array
    {
        return $this->updateBookingStatusAction->handle($booking, $status);
    }

    public function checkRoomAvailability(Room $room, string $checkInDate, string $checkOutDate, int $numberOfRooms): array
    {
        return $this->checkRoomAvailabilityAction->handle($room, $checkInDate, $checkOutDate, $numberOfRooms);
    }

    public function calculateRefundAmount(Booking $booking, bool $isStaffCancellation = false): float
    {
        if ($isStaffCancellation) {
            // Staff cancellation - always 80% refund
            return round($booking->total_amount * 0.80, 2);
        }

        // User cancellation - follow normal refund rules
        return $booking->getRefundAmount();
    }
}
