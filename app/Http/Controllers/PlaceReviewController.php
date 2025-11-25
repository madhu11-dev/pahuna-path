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
}