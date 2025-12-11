<?php

namespace App\Services;

use App\Actions\PlaceActions\CreatePlaceAction;
use App\Actions\PlaceActions\UpdatePlaceAction;
use App\Actions\PlaceActions\DeletePlaceAction;
use App\Models\Place;
use App\Models\PlaceReview;
use App\Models\User;

class PlaceService
{
    public function __construct(
        protected CreatePlaceAction $createPlaceAction,
        protected UpdatePlaceAction $updatePlaceAction,
        protected DeletePlaceAction $deletePlaceAction,
        protected FileUploadService $fileUploadService
    ) {}

    public function createPlace(array $data, ?User $user = null): Place
    {
        // Handle image uploads
        if (isset($data['images'])) {
            $request = request();
            $data['images'] = $this->fileUploadService->uploadMultiple(
                $request->file('images'),
                'places',
                $request
            );
        }

        // Extract coordinates from Google Maps link
        if (isset($data['google_map_link'])) {
            $coords = $this->extractLocation($data['google_map_link']);
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }
        }

        // Set user ID
        $data['user_id'] = $user?->id ?? auth()->id() ?? 1;

        return $this->createPlaceAction->handle($data);
    }

    public function updatePlace(Place $place, array $data): Place
    {
        // Handle image uploads
        if (isset($data['images']) && request()->hasFile('images')) {
            // Delete old images
            if ($place->images) {
                foreach ($place->images as $imageUrl) {
                    $this->fileUploadService->deleteProfilePicture($imageUrl);
                }
            }

            $data['images'] = $this->fileUploadService->uploadMultiple(
                request()->file('images'),
                'places',
                request()
            );
        }

        // Extract coordinates from Google Maps link
        if (isset($data['google_map_link'])) {
            $coords = $this->extractLocation($data['google_map_link']);
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }
        }

        return $this->updatePlaceAction->handle($place, $data);
    }

    public function deletePlace(Place $place): void
    {
        // Delete images
        if ($place->images) {
            foreach ($place->images as $imageUrl) {
                $this->fileUploadService->deleteProfilePicture($imageUrl);
            }
        }

        $this->deletePlaceAction->handle($place);
    }

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
    public function getPlaceWithReviewStats(Place $place): array
    {
        $reviewStats = PlaceReview::where('place_id', $place->id)
            ->selectRaw('AVG(rating) as average_rating, COUNT(*) as review_count')
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

    /**
     * Get place images for slider/carousel
     */
    public function getPlaceImages(int $limit = 15): array
    {
        $places = Place::whereNotNull('images')
            ->select('images')
            ->limit(50)
            ->get();

        $allImages = [];
        
        foreach ($places as $place) {
            $images = $place->images;
            if (is_string($images)) {
                $images = json_decode($images, true);
            }
            
            if ($images && is_array($images) && count($images) > 0) {
                foreach ($images as $imagePath) {
                    if ($imagePath && !empty(trim($imagePath))) {
                        $allImages[] = $imagePath;
                    }
                }
            }
        }

        return array_slice($allImages, 0, $limit);
    }
}

