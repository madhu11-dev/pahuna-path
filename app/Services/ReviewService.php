<?php

namespace App\Services;

use App\Actions\ReviewActions\CreatePlaceReviewAction;
use App\Actions\ReviewActions\CreateAccommodationReviewAction;
use App\Models\Accommodation;
use App\Models\AccommodationReview;
use App\Models\Place;
use App\Models\PlaceReview;
use App\Models\User;

class ReviewService
{
    public function __construct(
        protected CreatePlaceReviewAction $createPlaceReviewAction,
        protected CreateAccommodationReviewAction $createAccommodationReviewAction
    ) {}

    public function createPlaceReview(Place $place, User $user, array $data): array
    {
        // Check if user already reviewed this place
        $existingReview = PlaceReview::where('place_id', $place->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            return [
                'success' => false,
                'message' => 'You have already reviewed this place.'
            ];
        }

        $review = $this->createPlaceReviewAction->handle($data, $user->id, $place->id);
        $review->load('user');

        return [
            'success' => true,
            'review' => $review
        ];
    }

    public function createAccommodationReview(Accommodation $accommodation, User $user, array $data): array
    {
        // Check if user already reviewed this accommodation
        $existingReview = AccommodationReview::where('accommodation_id', $accommodation->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingReview) {
            return [
                'success' => false,
                'message' => 'You have already reviewed this accommodation.'
            ];
        }

        $review = $this->createAccommodationReviewAction->handle($data, $user->id, $accommodation->id);
        $review->load('user');

        return [
            'success' => true,
            'review' => $review
        ];
    }
}

