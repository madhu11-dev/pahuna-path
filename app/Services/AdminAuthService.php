<?php

namespace App\Services;

use App\Models\User;
use App\Models\Place;
use App\Models\PlaceReview;
use App\Models\Accommodation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminAuthService
{
    public function adminLogout($user): bool
    {
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
            return true;
        }
        
        return false;
    }

    public function getDashboardStats(): array
    {
        $totalUsers = User::count();
        $totalPlaces = Place::count();
        $totalHotels = Accommodation::count();
        $totalReviews = PlaceReview::count();
        
        // Get monthly visitor data for graph (using place reviews as proxy for visits)
        $monthlyVisits = PlaceReview::select(
            DB::raw('MONTH(created_at) as month'),
            DB::raw('COUNT(*) as visits')
        )
        ->whereYear('created_at', date('Y'))
        ->groupBy('month')
        ->orderBy('month')
        ->get()
        ->mapWithKeys(function ($item) {
            return [$item->month => $item->visits];
        });

        // Fill missing months with 0
        $visitorGraphData = [];
        for ($i = 1; $i <= 12; $i++) {
            $visitorGraphData[] = [
                'month' => date('M', mktime(0, 0, 0, $i, 1)),
                'visits' => $monthlyVisits->get($i, 0)
            ];
        }

        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_visitors' => $totalReviews, // Using reviews as proxy for visitors
                'total_places' => $totalPlaces,
                'total_hotels' => $totalHotels,
                'total_reviews' => $totalReviews
            ],
            'visitor_graph_data' => $visitorGraphData
        ];
    }

    public function getAllUsers(): array
    {
        $users = User::select('id', 'name', 'email', 'created_at', 'profile_picture', 'utype')
            ->where('utype', 'USR') // Only get regular users, not admins
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    'profile_picture_url' => $user->profile_picture_url
                ];
            });

        return $users->toArray();
    }

    public function getAllPlaces(): array
    {
        $places = Place::with(['user:id,name,email,profile_picture', 'reviews' => function ($query) {
            $query->select('place_id', 'rating');
        }])
        ->select('id', 'place_name', 'description', 'images', 'google_map_link', 'latitude', 'longitude', 'user_id', 'is_merged', 'merged_from_ids', 'created_at')
        ->where('is_merged', false) // Only show non-merged places
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($place) {
            $averageRating = $place->reviews->avg('rating') ?? 0;
            $reviewCount = $place->reviews->count();
            
            return [
                'id' => $place->id,
                'place_name' => $place->place_name,
                'description' => $place->description,
                'images' => $place->images,
                'google_map_link' => $place->google_map_link,
                'latitude' => $place->latitude,
                'longitude' => $place->longitude,
                'user' => $place->user ? [
                    'id' => $place->user->id,
                    'name' => $place->user->name,
                    'email' => $place->user->email,
                    'profile_picture_url' => $place->user->profile_picture_url ?? 'http://localhost:8090/images/default-profile.png',
                ] : null,
                'review_count' => $reviewCount,
                'average_rating' => round($averageRating, 1),
                'is_merged' => $place->is_merged,
                'merged_from_ids' => $place->merged_from_ids,
                'created_at' => $place->created_at->format('Y-m-d H:i:s')
            ];
        });

        return $places->toArray();
    }

    public function getAllAccommodations(): array
    {
        $accommodations = Accommodation::select('id', 'name', 'description', 'image_path', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($accommodation) {
                return [
                    'id' => $accommodation->id,
                    'name' => $accommodation->name,
                    'description' => $accommodation->description,
                    'image_url' => $accommodation->image_path ? asset($accommodation->image_path) : null,
                    'created_at' => $accommodation->created_at->format('Y-m-d H:i:s')
                ];
            });

        return $accommodations->toArray();
    }

    public function deletePlace(Place $place): bool
    {
        try {
            // Delete associated files from storage
            if ($place->images && is_array($place->images)) {
                foreach ($place->images as $image) {
                    $imagePath = storage_path('app/public/' . $image);
                    if (file_exists($imagePath)) {
                        unlink($imagePath);
                    }
                }
            }

            // Delete the place (reviews will be cascade deleted due to foreign key constraint)
            $place->delete();
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function mergePlaces(array $placeIds, int $primaryPlaceId, array $options): array
    {
        try {
            DB::beginTransaction();

            // Validate that primary place is in the list
            if (!in_array($primaryPlaceId, $placeIds)) {
                return [
                    'success' => false,
                    'message' => 'Primary place must be one of the selected places'
                ];
            }

            // Get all places to merge
            $places = Place::whereIn('id', $placeIds)->with(['reviews', 'user'])->get();
            
            if ($places->count() !== count($placeIds)) {
                return [
                    'success' => false,
                    'message' => 'Some places could not be found'
                ];
            }

            $primaryPlace = $places->where('id', $primaryPlaceId)->first();
            $placesToMerge = $places->where('id', '!=', $primaryPlaceId);

            // Collect all images and reviews
            $allImages = [];
            $allReviews = collect();

            foreach ($places as $place) {
                // Collect images
                if ($place->images && is_array($place->images)) {
                    $allImages = array_merge($allImages, $place->images);
                }

                // Collect reviews
                $allReviews = $allReviews->merge($place->reviews);
            }

            // Update primary place
            $updateData = [];

            // Handle images
            if (!$options['keepPrimaryImages'] && !empty($allImages)) {
                $updateData['images'] = array_unique($allImages);
            }

            // Handle description
            if (!$options['keepPrimaryDescription']) {
                $descriptions = [];
                foreach ($places as $place) {
                    if ($place->description) {
                        $descriptions[] = $place->description;
                    }
                }
                if (!empty($descriptions)) {
                    $updateData['description'] = implode(' | ', array_unique($descriptions));
                }
            }

            // Track merged place IDs
            $mergedFromIds = $placesToMerge->pluck('id')->toArray();
            $existingMergedIds = $primaryPlace->merged_from_ids ?? [];
            $updateData['merged_from_ids'] = array_unique(array_merge($existingMergedIds, $mergedFromIds));

            // Update primary place
            if (!empty($updateData)) {
                $primaryPlace->update($updateData);
            }

            // Handle reviews merging
            if ($options['mergeReviews']) {
                foreach ($placesToMerge as $place) {
                    // Update all reviews to point to primary place
                    PlaceReview::where('place_id', $place->id)
                        ->update(['place_id' => $primaryPlaceId]);
                }
            } else {
                // Delete reviews from places being merged
                foreach ($placesToMerge as $place) {
                    PlaceReview::where('place_id', $place->id)->delete();
                }
            }

            // Mark merged places as merged and hide them
            foreach ($placesToMerge as $place) {
                $place->update([
                    'is_merged' => true,
                    'merged_from_ids' => [$primaryPlaceId] // Track which place this was merged into
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Places merged successfully',
                'data' => [
                    'primary_place_id' => $primaryPlaceId,
                    'merged_place_ids' => $mergedFromIds,
                    'total_reviews' => $allReviews->count()
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Failed to merge places: ' . $e->getMessage()
            ];
        }
    }
}