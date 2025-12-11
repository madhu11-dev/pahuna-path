<?php

namespace Database\Factories;

use App\Models\AccommodationReview;
use App\Models\User;
use App\Models\Accommodation;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccommodationReviewFactory extends Factory
{
    protected $model = AccommodationReview::class;

    public function definition(): array
    {
        return [
            'accommodation_id' => Accommodation::factory(),
            'user_id' => User::factory(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
        ];
    }
}
