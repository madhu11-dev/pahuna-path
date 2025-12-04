<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccommodationReviewRequest;
use App\Models\Accommodation;
use App\Models\AccommodationReview;
use Illuminate\Http\Request;

class AccommodationReviewController extends Controller
{
    public function index($accommodationId)
    {
        try {
            $accommodation = Accommodation::findOrFail($accommodationId);
            $reviews = AccommodationReview::where('accommodation_id', $accommodationId)
                ->with('user')
                ->latest()
                ->get();

            // Transform to simple array to avoid model issues
            $result = [];
            foreach ($reviews as $review) {
                $result[] = [
                    'id' => $review->id,
                    'accommodation_id' => $review->accommodation_id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'user' => $review->user ? [
                        'id' => $review->user->id,
                        'name' => $review->user->name,
                    ] : [
                        'id' => null,
                        'name' => 'Anonymous',
                    ],
                    'created_at' => $review->created_at,
                    'updated_at' => $review->updated_at,
                ];
            }

            return response()->json([
                'status' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch reviews: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }

    public function store(StoreAccommodationReviewRequest $request, Accommodation $accommodation)
    {
        try {
            $data = $request->validated();
            $data['accommodation_id'] = $accommodation->id;
            $data['user_id'] = auth()->id() ?? 1; // Fallback to user 1 if not authenticated

            // Check if user already reviewed this accommodation
            $existingReview = AccommodationReview::where('accommodation_id', $accommodation->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if ($existingReview) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already reviewed this accommodation.'
                ], 422);
            }

            $review = AccommodationReview::create($data);
            $review->load('user');

            // Update accommodation's average rating and review count
            $accommodation->updateAverageRating();

            // Return simple array structure
            $result = [
                'id' => $review->id,
                'accommodation_id' => $review->accommodation_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : [
                    'id' => null,
                    'name' => 'Anonymous',
                ],
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];

            return response()->json([
                'status' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to create review: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(StoreAccommodationReviewRequest $request, Accommodation $accommodation, AccommodationReview $review)
    {
        try {
            // Check if user owns this review
            if ($review->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to update this review.'
                ], 403);
            }

            // Check if review belongs to this accommodation
            if ($review->accommodation_id !== $accommodation->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review does not belong to this accommodation.'
                ], 422);
            }

            $data = $request->validated();
            $review->update($data);
            $review->load('user');

            // Update accommodation's average rating and review count
            $accommodation->updateAverageRating();

            // Return simple array structure
            $result = [
                'id' => $review->id,
                'accommodation_id' => $review->accommodation_id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : [
                    'id' => null,
                    'name' => 'Anonymous',
                ],
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,
            ];

            return response()->json([
                'status' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to update review: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Accommodation $accommodation, AccommodationReview $review)
    {
        try {
            // Check if user owns this review
            if ($review->user_id !== auth()->id()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized to delete this review.'
                ], 403);
            }

            // Check if review belongs to this accommodation
            if ($review->accommodation_id !== $accommodation->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Review does not belong to this accommodation.'
                ], 422);
            }

            $review->delete();

            // Update accommodation's average rating and review count
            $accommodation->updateAverageRating();

            return response()->json([
                'status' => true,
                'message' => 'Review deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to delete review: ' . $e->getMessage()
            ], 500);
        }
    }
}
