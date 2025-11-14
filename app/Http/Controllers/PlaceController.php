<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Place;
use App\Http\Resources\PlaceResource;
use Illuminate\Support\Facades\Log;

class PlaceController extends Controller
{
    // GET all places
    public function index()
    {
        $places = Place::orderBy('created_at', 'desc')->get();
        return PlaceResource::collection($places);
    }

    // POST new place (only for logged-in users)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'place_name' => 'required|string|max:255',
            'google_map_link' => 'required|string|max:255',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|string',
            'caption' => 'required|string',
            'review' => 'required|numeric|min:0|max:5',
            'location.latitude' => 'nullable|numeric|between:-90,90',
            'location.longitude' => 'nullable|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        // Extract location if not provided
        if (isset($validated['location']['latitude'], $validated['location']['longitude'])) {
            $location = [
                'latitude' => $validated['location']['latitude'],
                'longitude' => $validated['location']['longitude'],
            ];
        } else {
            $location = $this->extract_geo_location($validated['google_map_link']);
            if (!$location) {
                return response()->json([
                    'errors' => ['google_map_link' => 'Unable to extract latitude/longitude from link.']
                ], 422);
            }
        }

        $place = new Place();
        $place->place_name = $validated['place_name'];
        $place->images = $validated['images'];
        $place->google_map_link = $validated['google_map_link'];
        $place->caption = $validated['caption'];
        $place->review = $validated['review'];
        $place->user_id = auth()->id(); // logged-in user
        $place->location = $location;

        try {
            $place->save();
        } catch (\Exception $e) {
            Log::error('Failed to save place: ' . $e->getMessage());
            return response()->json(['errors' => ['database' => 'Failed to save place.']], 500);
        }

        return new PlaceResource($place);
    }

    // PUT/PATCH update a place
    public function update(Request $request, $id)
    {
        $place = Place::findOrFail($id);

        if ($place->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $validated = $validator->validated();

        if (isset($validated['location']['latitude'], $validated['location']['longitude'])) {
            $place->location = [
                'latitude' => $validated['location']['latitude'],
                'longitude' => $validated['location']['longitude'],
            ];
        } elseif (isset($validated['google_map_link'])) {
            $location = $this->extract_geo_location($validated['google_map_link']);
            if ($location) {
                $place->location = $location;
            }
        }

        foreach (['place_name','images','google_map_link','caption','review'] as $field) {
            if (isset($validated[$field])) {
                $place->$field = $validated[$field];
            }
        }

        try {
            $place->save();
        } catch (\Exception $e) {
            Log::error('Failed to update place: ' . $e->getMessage());
            return response()->json(['errors' => ['database' => 'Failed to update place.']], 500);
        }

        return new PlaceResource($place);
    }

    // DELETE a place
    public function destroy($id)
    {
        $place = Place::findOrFail($id);

        if ($place->user_id !== auth()->id()) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $place->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete place: ' . $e->getMessage());
            return response()->json(['errors' => ['database' => 'Failed to delete place.']], 500);
        }

        return response()->json(['message' => 'Place deleted successfully']);
    }

    // Extract lat/lng from Google Maps URL
    private function extract_geo_location($url)
    {
        if (preg_match('/@([-0-9.]+),([-0-9.]+),/', $url, $matches)) {
            return [
                'latitude' => (float)$matches[1],
                'longitude' => (float)$matches[2],
            ];
        }
        return null;
    }
}
