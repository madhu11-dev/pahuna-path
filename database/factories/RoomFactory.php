<?php

namespace Database\Factories;

use App\Models\Room;
use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'accommodation_id' => Accommodation::factory(),
            'room_name' => fake()->randomElement(['Deluxe', 'Standard', 'Suite', 'Family']) . ' Room',
            'room_type' => fake()->randomElement(['standard', 'deluxe', 'suite', 'family']),
            'has_ac' => fake()->boolean(80),
            'capacity' => fake()->numberBetween(1, 4),
            'total_rooms' => fake()->numberBetween(5, 20),
            'base_price' => fake()->randomFloat(2, 1000, 10000),
            'description' => fake()->sentence(),
            'images' => ['room1.jpg', 'room2.jpg'],
        ];
    }
}
