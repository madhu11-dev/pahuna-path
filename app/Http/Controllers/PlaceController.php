<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\PlaceService;

class PlaceController extends Controller
{
    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function index()
    {
        return PlaceResource::collection(Place::latest()->get());
    }

    public function store(StorePlaceRequest $request)
{
    $data = $request->validated();
    $data['user_id'] = 1;

    $coords = $this->placeService->extractLocation($data['google_map_link'] ?? '');
    if ($coords) {
        $data['latitude'] = $coords['latitude'];
        $data['longitude'] = $coords['longitude'];
    }

    try {
        $place = Place::create($data);
        return new PlaceResource($place);
    } catch (\Throwable $e) {
        // Dump exact error
        dd([
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}


    public function show(Place $place)
    {
        return new PlaceResource($place);
    }

    public function update(StorePlaceRequest $request, Place $place)
    {
        $data = $request->validated();

        $coords = $this->placeService->extractLocation($data['google_map_link']);
        if ($coords) {
            $data['latitude'] = $coords['latitude'];
            $data['longitude'] = $coords['longitude'];
        }

        $place->update(array_intersect_key($data, array_flip((new Place)->getFillable())));

        return new PlaceResource($place);
    }

    public function destroy(Place $place)
    {
        $place->delete();
        return response()->json(['message' => 'Place deleted successfully.']);
    }
}
