<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceReviewRequest;
use App\Http\Resources\PlaceReviewResource;
use App\Models\Place;
use App\Models\PlaceReview;
use Illuminate\Http\Request;

class PlaceReviewController extends Controller
{
    public function index(Place $place)
    {
        try {
            $reviews = $place->reviews()->with('user')->latest()->get();
            
            // Transform to simple array to avoid model issues
            $result = [];
            foreach ($reviews as $review) {
                $result[] = [
                    'id' => $review->id,
                    'place_id' => $review->place_id,
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

    public function store(StorePlaceReviewRequest $request, Place $place)
    {
        try {
            $data = $request->validated();
            $data['place_id'] = $place->id;
            $data['user_id'] = auth()->id() ?? 1; // Fallback to user 1 if not authenticated

            // Check if user already reviewed this place
            $existingReview = PlaceReview::where('place_id', $place->id)
                ->where('user_id', $data['user_id'])
                ->first();

            if ($existingReview) {
                return response()->json([
                    'status' => false,
                    'message' => 'You have already reviewed this place.'
                ], 422);
            }

            $review = PlaceReview::create($data);
            $review->load('user');

            // Return simple array structure
            $result = [
                'id' => $review->id,
                'place_id' => $review->place_id,
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

    public function update(StorePlaceReviewRequest $request, Place $place, PlaceReview $review)
    {
        // Ensure user can only update their own review
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'You can only update your own reviews.'
            ], 403);
        }

        $review->update($request->validated());
        $review->load('user');

        return new PlaceReviewResource($review);
    }

    public function destroy(Place $place, PlaceReview $review)
    {
        // Ensure user can only delete their own review
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'You can only delete your own reviews.'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'status' => true,
            'message' => 'Review deleted successfully.'
        ]);
    }
}