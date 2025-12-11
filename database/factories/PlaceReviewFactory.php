<?php

namespace Database\Factories;

use App\Models\PlaceReview;
use App\Models\User;
use App\Models\Place;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaceReviewFactory extends Factory
{
    protected $model = PlaceReview::class;

    public function definition(): array
    {
        return [
            'place_id' => Place::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
        ];
    }
}
