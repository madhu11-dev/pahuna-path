<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceRequest;
use App\Http\Requests\UpdatePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Http\JsonResponse;

class PlaceController extends Controller
{
    public function __construct(protected PlaceService $placeService) {}

    /**
     * Get all places with review stats
     */
    public function index(): JsonResponse
    {
        $places = Place::with('user')->latest()->get();

        $result = $places->map(function ($place) {
            return $this->placeService->getPlaceWithReviewStats($place);
        });

        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }

    /**
     * Create a new place
     */
    public function store(StorePlaceRequest $request): JsonResponse
    {
        $data = $request->validated();
        $place = $this->placeService->createPlace($data, $request->user());

        return response()->json([
            'status' => true,
            'data' => new PlaceResource($place)
        ], 201);
    }

    /**
     * Show single place with review stats
     */
    public function show(Place $place): JsonResponse
    {
        $place->load('user');
        $result = $this->placeService->getPlaceWithReviewStats($place);

        return response()->json([
            'status' => true,
            'data' => $result
        ]);
    }

    /**
     * Update place
     */
    public function update(UpdatePlaceRequest $request, Place $place): JsonResponse
    {
        $data = $request->validated();
        $place = $this->placeService->updatePlace($place, $data);

        return response()->json([
            'status' => true,
            'data' => new PlaceResource($place)
        ]);
    }

    /**
     * Delete place
     */
    public function destroy(Place $place): JsonResponse
    {
        $this->placeService->deletePlace($place);

        return response()->json([
            'status' => true,
            'message' => 'Place deleted successfully'
        ]);
    }

    /**
     * Get place images for landing page slider
     */
    public function getPlaceImages(): JsonResponse
    {
        $images = $this->placeService->getPlaceImages(15);

        return response()->json([
            'status' => true,
            'images' => $images
        ]);
    }
}
