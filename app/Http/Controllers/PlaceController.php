<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePlaceRequest;
use App\Http\Resources\PlaceResource;
use App\Models\Place;
use App\Services\PlaceService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PlaceController extends Controller
{
    protected PlaceService $placeService;

    public function __construct(PlaceService $placeService)
    {
        $this->placeService = $placeService;
    }

    public function index()
    {
        try {
            // Get places with user relationship
            $places = Place::with('user')->orderBy('created_at', 'desc')->get();
            
            // Transform using PlaceService
            $result = [];
            foreach ($places as $place) {
                $result[] = $this->placeService->getPlaceWithReviewStats($place);
            }
            
            return response()->json([
                'status' => true,
                'data' => $result
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch places: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(StorePlaceRequest $request)
    {
        try {
            $data = $request->only(['place_name', 'description', 'google_map_link']);
            $data['user_id'] = auth()->id() ?? 1; // Get authenticated user or fallback to 1

            $imageUrls = [];
            $files = array_filter(Arr::wrap($request->file('images')));

            foreach ($files as $file) {
                if (!$file || !$file->isValid()) {
                    Log::warning('Invalid image upload skipped', [
                        'file' => $file ? $file->getClientOriginalName() : null,
                    ]);
                    continue;
                }

                try {
                    if (!Storage::disk('public')->exists('places')) {
                        Storage::disk('public')->makeDirectory('places');
                    }

                    $path = $file->store('places', 'public');

                    if (!$path) {
                        Log::error('Failed to store file: ' . $file->getClientOriginalName());
                        continue;
                    }

                    $imageUrls[] = $this->buildPublicStorageUrl($request, $path);
                } catch (\Throwable $e) {
                    Log::error('Error storing file: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString(),
                    ]);
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

            // Use direct coordinates from frontend if provided, otherwise extract from Google Maps link
            if (isset($data['latitude']) && isset($data['longitude'])) {
                // Direct coordinates provided from frontend
                $data['latitude'] = (float) $data['latitude'];
                $data['longitude'] = (float) $data['longitude'];
            } else {
                // Fallback to extracting from Google Maps link
                $coords = $this->placeService->extractLocation($data['google_map_link'] ?? '');
                if ($coords) {
                    $data['latitude'] = $coords['latitude'];
                    $data['longitude'] = $coords['longitude'];
                }
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
        try {
            $place->load('user');
            $result = $this->placeService->getPlaceWithReviewStats($place);
            
            return response()->json([
                'status' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch place details: ' . $e->getMessage(),
            ], 500);
        }
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

            $imageUrls = [];
            foreach ($files as $file) {
                $path = $file->store('places', 'public');
                $imageUrls[] = $this->buildPublicStorageUrl($request, $path);
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

    protected function buildPublicStorageUrl($request, string $path): string
    {
        $rawUrl = Storage::disk('public')->url($path);
        $rawUrl = str_replace('/public/', '/', $rawUrl);

        $baseUrl = $this->resolveBaseUrl($request);

        if (Str::startsWith($rawUrl, ['http://', 'https://'])) {
            return preg_replace('#^https?://[^/]+#', $baseUrl, $rawUrl);
        }

        return $baseUrl . '/' . ltrim($rawUrl, '/');
    }

    protected function resolveBaseUrl($request): string
    {
        $baseUrl = rtrim(config('app.url') ?? '', '/');

        if (!$baseUrl) {
            $baseUrl = rtrim($request->getSchemeAndHttpHost() ?? '', '/');
        }

        if (!$baseUrl) {
            $baseUrl = 'http://localhost';
        }

        $parsed = parse_url($baseUrl);
        $port = $parsed['port'] ?? env('APP_PORT', 8090);

        if (!isset($parsed['port']) && $port && !in_array((int) $port, [80, 443])) {
            $baseUrl .= ':' . $port;
        }

        return $baseUrl;
    }

    /**
     * Get place images for landing page slider
     * Only returns image URLs for security
     */
    public function getPlaceImages()
    {
        try {
            // Get places with images - using database-agnostic approach
            $places = Place::whereNotNull('images')
                          ->select('images')
                          ->limit(50) // Get more places to have enough images
                          ->get();

            $allImages = [];
            
            foreach ($places as $place) {
                // Ensure images is properly decoded if stored as JSON string
                $images = $place->images;
                if (is_string($images)) {
                    $images = json_decode($images, true);
                }
                
                if ($images && is_array($images) && count($images) > 0) {
                    foreach ($images as $imagePath) {
                        if ($imagePath && !empty(trim($imagePath))) {
                            $allImages[] = $imagePath;
                        }
                    }
                }
            }

            // Limit to 15 images for the slider
            $limitedImages = array_slice($allImages, 0, 15);

            return response()->json([
                'status' => true,
                'images' => $limitedImages
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error fetching place images: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch place images: ' . $e->getMessage(),
                'images' => []
            ], 500);
        }
    }
}
