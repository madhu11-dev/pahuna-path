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
        $reviewStats = PlaceReview::where('place_id', $place->id)
            ->selectRaw('
                    AVG(rating) as average_rating,
                    COUNT(*) as review_count
                ')
            ->first();

        return [
            'id' => $place->id,
            'place_name' => $place->place_name,
            'description' => $place->description,
            'images' => $place->images,
            'google_map_link' => $place->google_map_link,
            'latitude' => $place->latitude,
            'longitude' => $place->longitude,
            'is_verified' => $place->is_verified,
            'user' => $place->user ? [
                'id' => $place->user->id,
                'name' => $place->user->name,
                'profile_picture_url' => $place->user->profile_picture_url ?? 'http://localhost:8090/images/default-profile.png',
            ] : null,
            'average_rating' => $reviewStats ? round((float) $reviewStats->average_rating, 1) : 0,
            'review_count' => $reviewStats ? (int) $reviewStats->review_count : 0,
            'created_at' => $place->created_at,
            'updated_at' => $place->updated_at,
        ];

    }
}
