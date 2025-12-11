<?php

namespace App\Services;

use App\Models\User;
use App\Models\Place;
use App\Models\PlaceReview;
use App\Models\Accommodation;
use App\Models\AccommodationReview;

use Illuminate\Support\Facades\DB;


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
        $totalAccommodations = Accommodation::count();
        $totalPlaceReviews = PlaceReview::count();
        $totalAccommodationReviews = AccommodationReview::count();
        $totalReviews = $totalPlaceReviews + $totalAccommodationReviews;

        // Get monthly user registration data for graph 
        $monthlyUserRegistrations = User::select(
            DB::raw('EXTRACT(MONTH FROM created_at) AS month'),
            DB::raw('COUNT(*) AS user_count')
        )
            ->whereYear('created_at', date('Y'))
            ->groupBy(DB::raw('month'))
            ->orderBy('month')
            ->get()
            ->mapWithKeys(function ($item) {
                return [(int)$item->month => $item->user_count];
            });

        // Fill missing months with 0
        $userGraphData = [];
        for ($i = 1; $i <= 12; $i++) {
            $userGraphData[] = [
                'month' => date('M', mktime(0, 0, 0, $i, 1)),
                'users' => $monthlyUserRegistrations->get($i, 0)
            ];
        }

        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_places' => $totalPlaces,
                'total_accommodations' => $totalAccommodations,
                'total_reviews' => $totalReviews
            ],
            'user_graph_data' => $userGraphData
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
                    'utype' => $user->utype,
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
            ->select('id', 'place_name', 'description', 'images', 'google_map_link', 'latitude', 'longitude', 'user_id', 'is_merged', 'merged_from_ids', 'is_verified', 'created_at')
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
                    'is_verified' => $place->is_verified,
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

    public function deleteUser(User $user): bool
    {
        try {
            // Delete user's profile picture if exists
            if ($user->profile_picture && file_exists(public_path($user->profile_picture))) {
                unlink(public_path($user->profile_picture));
            }

            // Delete associated places and their images
            $userPlaces = Place::where('user_id', $user->id)->get();
            foreach ($userPlaces as $place) {
                // Delete place images
                if ($place->images && is_array($place->images)) {
                    foreach ($place->images as $image) {
                        $imagePath = storage_path('app/public/' . $image);
                        if (file_exists($imagePath)) {
                            unlink($imagePath);
                        }
                    }
                }
            }

            // Delete the user (places and reviews will be cascade deleted due to foreign key constraints)
            $user->delete();

            return true;
        } catch (\Exception $e) {
            return false;
        }
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

    public function mergePlaces(array $placeIds, array $mergeData): array
    {
        try {
            DB::beginTransaction();

            // Get all places to merge
            $places = Place::whereIn('id', $placeIds)->with(['reviews', 'user'])->get();

            if ($places->count() !== count($placeIds)) {
                return [
                    'success' => false,
                    'message' => 'Some places could not be found'
                ];
            }

            // Collect all reviews from places being merged
            $allReviews = collect();
            foreach ($places as $place) {
                $allReviews = $allReviews->merge($place->reviews);
            }

            // Create new merged place
            $newPlace = Place::create([
                'place_name' => $mergeData['selectedPlaceName'],
                'description' => $mergeData['selectedDescription'],
                'images' => $mergeData['selectedImages'], // Array of selected images
                'google_map_link' => $mergeData['selectedLocation'],
                'latitude' => $mergeData['selectedLatitude'] ?? null,
                'longitude' => $mergeData['selectedLongitude'] ?? null,
                'user_id' => $mergeData['userId'], // Admin user ID who performed the merge
                'is_merged' => false, // This is the new main place
                'merged_from_ids' => $placeIds // Track which places were merged to create this
            ]);

            // Transfer all reviews to the new merged place
            foreach ($allReviews as $review) {
                $review->update(['place_id' => $newPlace->id]);
            }

            // Delete the original places (reviews are already transferred)
            Place::whereIn('id', $placeIds)->delete();

            // Clean up any orphaned image files from deleted places
            foreach ($places as $place) {
                if ($place->images && is_array($place->images)) {
                    foreach ($place->images as $image) {
                        // Only delete images that are not used in the new merged place
                        if (!in_array($image, $mergeData['selectedImages'])) {
                            $imagePath = storage_path('app/public/' . $image);
                            if (file_exists($imagePath)) {
                                unlink($imagePath);
                            }
                        }
                    }
                }
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Places merged successfully into new place',
                'data' => [
                    'new_place_id' => $newPlace->id,
                    'merged_place_ids' => $placeIds,
                    'total_reviews' => $allReviews->count(),
                    'place_name' => $newPlace->place_name
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

    public function getAllStaff(): array
    {
        $allStaff = User::where('utype', 'STF')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($staff) {
                return [
                    'id' => $staff->id,
                    'name' => $staff->name,
                    'email' => $staff->email,
                    'phone' => $staff->phone,
                    'email_verified_at' => $staff->email_verified_at,
                    'created_at' => $staff->created_at->format('Y-m-d H:i:s'),
                ];
            });

        return [
            'success' => true,
            'data' => $allStaff
        ];
    }
}
