<?php

namespace Database\Factories;

use App\Models\Accommodation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccommodationFactory extends Factory
{
    protected $model = Accommodation::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company() . ' ' . fake()->randomElement(['Hotel', 'Guesthouse', 'Resort']),
            'images' => ['image1.jpg', 'image2.jpg'],
            'type' => fake()->randomElement(['hotel', 'restaurant', 'guesthouse']),
            'google_map_link' => 'https://maps.google.com/?q=' . fake()->latitude() . ',' . fake()->longitude(),
            'description' => fake()->paragraph(),
            'latitude' => fake()->latitude(27.0, 28.5),
            'longitude' => fake()->longitude(83.0, 86.0),
            'checkout_policy' => 'Check-out at 11 AM',
            'cancellation_policy' => 'Free cancellation 24h before',
            'staff_id' => User::factory()->staff(),
            'is_verified' => false,
            'average_rating' => null,
            'review_count' => 0,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
