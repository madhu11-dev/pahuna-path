<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Services\AccommodationService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AccommodationController extends Controller
{
    protected AccommodationService $accommodationService;

    public function __construct(AccommodationService $accommodationService)
    {
        $this->accommodationService = $accommodationService;
    }

    public function index()
    {
        return AccommodationResource::collection(Accommodation::latest()->get());
    }

    public function store(StoreAccommodationRequest $request)
    {
        try {
            $data = $request->only(['name', 'type', 'description', 'review', 'google_map_link', 'place_id']);
            $data['user_id'] = 1;

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
                    if (!Storage::disk('public')->exists('accommodations')) {
                        Storage::disk('public')->makeDirectory('accommodations');
                    }

                    $path = $file->store('accommodations', 'public');

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

            $coords = $this->accommodationService->extractLocation($data['google_map_link'] ?? '');
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }

            $accommodation = Accommodation::create($data);
            return new AccommodationResource($accommodation);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Accommodation creation error: ' . $e->getMessage());
            
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Accommodation $accommodation)
    {
        if ($accommodation->images) {
            foreach ($accommodation->images as $imageUrl) {
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

        $accommodation->delete();
        return response()->json(['message' => 'Accommodation deleted successfully.']);
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
}

