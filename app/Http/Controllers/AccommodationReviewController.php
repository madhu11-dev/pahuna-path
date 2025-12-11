<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccommodationReviewRequest;
use App\Http\Resources\AccommodationReviewResource;
use App\Models\Accommodation;
use App\Models\AccommodationReview;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;

class AccommodationReviewController extends Controller
{
    public function __construct(protected ReviewService $reviewService) {}

    /**
     * Get all reviews for an accommodation
     */
    public function index($accommodationId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $reviews = AccommodationReview::where('accommodation_id', $accommodationId)
            ->with('user')
            ->latest()
            ->get();

        return response()->json([
            'status' => true,
            'data' => AccommodationReviewResource::collection($reviews)
        ]);
    }

    /**
     * Create a review for an accommodation
     * Middleware: auth:sanctum
     */
    public function store(StoreAccommodationReviewRequest $request, Accommodation $accommodation): JsonResponse
    {
        $result = $this->reviewService->createAccommodationReview(
            $accommodation,
            $request->user(),
            $request->validated()
        );

        if (!$result['success']) {
            return response()->json([
                'status' => false,
                'message' => $result['message']
            ], 422);
        }

        // Update accommodation's average rating
        $accommodation->updateAverageRating();

        return response()->json([
            'status' => true,
            'data' => new AccommodationReviewResource($result['review'])
        ], 201);
    }

    /**
     * Update a review
     * Middleware: auth:sanctum
     */
    public function update(StoreAccommodationReviewRequest $request, Accommodation $accommodation, AccommodationReview $review): JsonResponse
    {
        // Authorization: only owner can update
        if ($review->user_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to update this review.'
            ], 403);
        }

        // Verify review belongs to accommodation
        if ($review->accommodation_id !== $accommodation->id) {
            return response()->json([
                'status' => false,
                'message' => 'Review does not belong to this accommodation.'
            ], 422);
        }

        $review->update($request->validated());
        $review->load('user');

        // Update accommodation's average rating
        $accommodation->updateAverageRating();

        return response()->json([
            'status' => true,
            'data' => new AccommodationReviewResource($review)
        ]);
    }

    /**
     * Delete a review
     * Middleware: auth:sanctum
     */
    public function destroy(Accommodation $accommodation, AccommodationReview $review): JsonResponse
    {
        // Authorization: only owner can delete
        if ($review->user_id !== auth()->id()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized to delete this review.'
            ], 403);
        }

        // Verify review belongs to accommodation
        if ($review->accommodation_id !== $accommodation->id) {
            return response()->json([
                'status' => false,
                'message' => 'Review does not belong to this accommodation.'
            ], 422);
        }

        $review->delete();

        // Update accommodation's average rating
        $accommodation->updateAverageRating();

        return response()->json([
            'status' => true,
            'message' => 'Review deleted successfully.'
        ]);
    }
}

