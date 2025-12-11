<?php

namespace Database\Factories;

use App\Models\Place;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaceFactory extends Factory
{
    protected $model = Place::class;

    public function definition(): array
    {
        return [
            'place_name' => fake()->city() . ' ' . fake()->randomElement(['Temple', 'Park', 'Museum']),
            'description' => fake()->paragraph(),
            'images' => ['place1.jpg', 'place2.jpg'],
            'google_map_link' => 'https://maps.google.com/?q=' . fake()->latitude() . ',' . fake()->longitude(),
            'latitude' => fake()->latitude(27.0, 28.5),
            'longitude' => fake()->longitude(83.0, 86.0),
            'user_id' => User::factory(),
            'is_merged' => false,
            'merged_from_ids' => null,
            'is_verified' => false,
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_verified' => true,
        ]);
    }
}
