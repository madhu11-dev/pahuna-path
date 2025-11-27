<?php

namespace App\Services;

use App\Models\Place;
use App\Models\PlaceReview;

class PlaceService
{
    public function extractLocation(string $googleMapLink): ?array
    {
        if (preg_match('/@([-0-9.]+),([-0-9.]+)/', $googleMapLink, $matches)) {
            return ['latitude' => (float) $matches[1], 'longitude' => (float) $matches[2]];
        }

        if (preg_match('/[?&]q=([-0-9.]+),([-0-9.]+)/', $googleMapLink, $matches)) {
            return ['latitude' => (float) $matches[1], 'longitude' => (float) $matches[2]];
        }

        return null;
    }

    /**
     * Get place data with review statistics
     */
    public function getPlaceWithReviewStats($place)
    {
        

    }
}
