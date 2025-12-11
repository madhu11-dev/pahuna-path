<?php

namespace App\Actions\ReviewActions;

use App\Models\AccommodationReview;

class CreateAccommodationReviewAction
{
    public function handle(array $data, int $userId, int $accommodationId): AccommodationReview
    {
        return AccommodationReview::create([
            'user_id' => $userId,
            'accommodation_id' => $accommodationId,
            'rating' => $data['rating'],
            'comment' => $data['comment'] ?? null,
        ]);
    }
}
