<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
        try {
            $data = $request->only(['place_name', 'caption', 'review', 'google_map_link']);
            $data['user_id'] = 1;

            $imageUrls = [];
            if ($request->hasFile('images')) {
                $files = $request->file('images');
                
                if (!is_array($files)) {
                    $files = [$files];
                }
                
                if (count($files) > 5) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Maximum 5 images allowed.',
                        'errors' => ['images' => ['Maximum 5 images allowed.']]
                    ], 422);
                }
                
                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        try {
                            if (!Storage::disk('public')->exists('places')) {
                                Storage::disk('public')->makeDirectory('places');
                            }
                            
                            $path = $file->store('places', 'public');
                            
                            if (!$path) {
                                Log::error('Failed to store file: ' . $file->getClientOriginalName());
                                continue;
                            }
                            
                            if (!Storage::disk('public')->exists($path)) {
                                Log::error('File does not exist after storage: ' . $path);
                                continue;
                            }
                            
                            $baseUrl = rtrim($request->getSchemeAndHttpHost() ?? config('app.url'), '/');
                            $url = $baseUrl . '/storage/' . ltrim($path, '/');
                            if (strpos($url, '/public/') !== false) {
                                $url = str_replace('/public/', '/', $url);
                            }
                            $imageUrls[] = $url;
                        } catch (\Exception $e) {
                            Log::error('Error storing file: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
                            continue;
                        }
                    } else {
                        Log::error('Invalid file: ' . ($file ? $file->getClientOriginalName() : 'null'));
                    }
                }
            }
            
            if (empty($imageUrls)) {
                return response()->json([
                    'status' => false,
                    'message' => 'No images were uploaded or files failed to save.',
                    'errors' => ['images' => ['At least one image file is required.']]
                ], 422);
            }
            
            $data['images'] = $imageUrls;

            $coords = $this->placeService->extractLocation($data['google_map_link'] ?? '');
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }

            $place = Place::create($data);
            return new PlaceResource($place);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Place creation error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


    public function show(Place $place)
    {
        return new PlaceResource($place);
    }

    public function update(StorePlaceRequest $request, Place $place)
    {
        $data = $request->validated();

        if ($request->hasFile('images')) {
            if ($place->images) {
                foreach ($place->images as $oldImageUrl) {
                    $parsedUrl = parse_url($oldImageUrl);
                    if (isset($parsedUrl['path'])) {
                        $path = ltrim($parsedUrl['path'], '/');
                        if (strpos($path, 'storage/') === 0) {
                            $path = substr($path, 8);
                        }
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
                }
            }

            $files = $request->file('images');
            if (!is_array($files)) {
                $files = [$files];
            }

            $baseUrl = rtrim($request->getSchemeAndHttpHost() ?? config('app.url'), '/');
            $imageUrls = [];
            foreach ($files as $file) {
                $path = $file->store('places', 'public');
                $url = $baseUrl . '/storage/' . ltrim($path, '/');
                if (strpos($url, '/public/') !== false) {
                    $url = str_replace('/public/', '/', $url);
                }
                $imageUrls[] = $url;
            }
            $data['images'] = $imageUrls;
        }

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
        if ($place->images) {
            foreach ($place->images as $imageUrl) {
                    $parsedUrl = parse_url($imageUrl);
                    if (isset($parsedUrl['path'])) {
                        $path = ltrim($parsedUrl['path'], '/');
                        if (strpos($path, 'storage/') === 0) {
                            $path = substr($path, 8);
                        }
                        if (Storage::disk('public')->exists($path)) {
                            Storage::disk('public')->delete($path);
                        }
                    }
            }
        }

        $place->delete();
        return response()->json(['message' => 'Place deleted successfully.']);
    }
}
