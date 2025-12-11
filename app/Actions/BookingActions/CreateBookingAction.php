<?php

namespace App\Actions\BookingActions;

use App\Models\Booking;
use App\Models\Room;
use App\Models\ExtraService;
use Carbon\Carbon;

class CreateBookingAction
{
    public function handle(array $data, int $userId): Booking
    {
        $room = Room::findOrFail($data['room_id']);
        
        $checkInDate = Carbon::parse($data['check_in_date']);
        $checkOutDate = Carbon::parse($data['check_out_date']);
        $totalNights = $checkInDate->diffInDays($checkOutDate);

        $roomSubtotal = $room->base_price * $data['number_of_rooms'] * $totalNights;
        
        $booking = Booking::create([
            'user_id' => $userId,
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

        // Handle services if provided
        if (!empty($data['services'])) {
            $servicesSubtotal = 0;
            
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

        return $booking->load(['room', 'accommodation', 'services.service']);
    }
}
