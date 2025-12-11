<?php

namespace App\Actions\ReviewActions;

use App\Models\PlaceReview;

class CreatePlaceReviewAction
{
    public function handle(array $data, int $userId, int $placeId): PlaceReview
    {
        return PlaceReview::create([
            'user_id' => $userId,
            'place_id' => $placeId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
