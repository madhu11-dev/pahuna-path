<?php

namespace App\Http\Controllers;

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
}