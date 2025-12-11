<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceReviewRequest;
use App\Http\Resources\PlaceReviewResource;
use App\Models\Place;
use App\Models\PlaceReview;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class PlaceReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    /**
     * Get all reviews for a place
     */
    public function index(Place $place): JsonResponse
    {
        $reviews = $place->reviews()->with('user')->latest()->get();

        return response()->json([
            'status' => true,
            'data' => PlaceReviewResource::collection($reviews)
        ]);
    }

    /**
     * Create a review for a place
     * Middleware: auth:sanctum
     */
    public function store(StorePlaceReviewRequest $request, Place $place): JsonResponse
    {
        $result = $this->reviewService->createPlaceReview(
            $place,
            $request->user(),
            $request->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message']
            ], 422);
        }

        return response()->json([
            'status' => true,
            'data' => new PlaceReviewResource($result['review'])
        ], 201);
    }

    /**
     * Update a review
     * Middleware: auth:sanctum
     */
    public function update(StorePlaceReviewRequest $request, Place $place, PlaceReview $review): JsonResponse
    {
        // Authorization: only owner can update
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only update your own reviews.'
            ], 403);
        }

        $review->update($request->validated());
        $review->load('user');

        return response()->json([
            'status' => true,
            'data' => new PlaceReviewResource($review)
        ]);
    }

    /**
     * Delete a review
     * Middleware: auth:sanctum
     */
    public function destroy(Place $place, PlaceReview $review): JsonResponse
    {
        // Authorization: only owner can delete
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
