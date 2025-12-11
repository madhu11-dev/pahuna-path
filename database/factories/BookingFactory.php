<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Accommodation;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = fake()->dateTimeBetween('now', '+1 week');
        $checkOut = fake()->dateTimeBetween($checkIn, '+2 weeks');
        $nights = $checkIn->diff($checkOut)->days;
        $basePrice = fake()->randomFloat(2, 1000, 5000);
        
        return [
            'booking_reference' => 'BK' . strtoupper(Str::random(8)),
            'user_id' => User::factory(),
            'accommodation_id' => Accommodation::factory(),
            'room_id' => Room::factory(),
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'check_in_time' => '14:00',
            'check_out_time' => '11:00',
            'number_of_rooms' => 1,
            'number_of_guests' => fake()->numberBetween(1, 4),
            'total_nights' => $nights,
            'room_subtotal' => $basePrice * $nights,
            'services_subtotal' => 0,
            'total_amount' => $basePrice * $nights,
            'booking_status' => 'confirmed',
            'payment_status' => 'paid',
            'payment_method' => 'khalti',
            'special_requests' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'booking_status' => 'checked_out',
        ]);
    }
}
