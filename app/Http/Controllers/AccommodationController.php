<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccommodationRequest;
use App\Http\Resources\AccommodationResource;
use App\Models\Accommodation;
use App\Services\AccommodationService;
use Illuminate\Http\Request;
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
        try {
            // Public endpoint - only show verified accommodations
            $accommodations = Accommodation::where('is_verified', true)
                ->with('staff')
                ->latest()
                ->get();

            $result = AccommodationResource::collection($accommodations);

            return response()->json([
                'status' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch accommodations: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function indexAll()
    {
        // Admin endpoint - show all accommodations
        return AccommodationResource::collection(Accommodation::latest()->get());
    }

    public function show(Accommodation $accommodation)
    {
        try {
            // Load reviews with users and staff
            $accommodation->load(['reviews.user', 'staff']);

            return response()->json([
                'status' => true,
                'data' => new AccommodationResource($accommodation)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Unable to fetch accommodation details: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreAccommodationRequest $request)
    {
        try {
            // Check if user is authenticated and is staff
            $user = $request->user();
            if (!$user || !$user->isStaff()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Only staff can create accommodations.'
                ], 403);
            }

            $data = $request->only(['name', 'type', 'description', 'google_map_link']);
            $data['staff_id'] = $user->id;
            $data['is_verified'] = false; // Default to unverified, admin can verify later

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

            // Allow accommodations without images for now (can be added later)
            if (empty($imageUrls)) {
                $imageUrls = []; // Empty array if no images
                Log::info('Accommodation created without images');
            }

            $data['images'] = $imageUrls;

            $coords = $this->accommodationService->extractLocation($data['google_map_link'] ?? '');
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }

            Log::info('About to create accommodation', [
                'data' => $data,
                'image_count' => count($imageUrls),
            ]);

            $accommodation = Accommodation::create($data);

            Log::info('Accommodation created successfully', [
                'accommodation_id' => $accommodation->id,
                'accommodation_name' => $accommodation->name,
            ]);

            return new AccommodationResource($accommodation);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Accommodation creation error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to create accommodation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(StoreAccommodationRequest $request, Accommodation $accommodation)
    {
        try {
            // Check if user is authenticated and is staff
            $user = $request->user();
            if (!$user || !$user->isStaff()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Only verified staff can update accommodations.'
                ], 403);
            }

            // Staff can update their accommodations

            // Check if staff owns this accommodation
            if ($accommodation->staff_id !== $user->id) {
                return response()->json([
                    'status' => false,
                    'message' => 'You can only update accommodations that you created.'
                ], 403);
            }

            $data = $request->only(['name', 'type', 'description', 'google_map_link']);

            // Handle image updates if provided
            if ($request->hasFile('images')) {
                // Delete old images if new ones are uploaded
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

                if (!empty($imageUrls)) {
                    $data['images'] = $imageUrls;
                }
            }

            // Update coordinates if map link changed
            if (isset($data['google_map_link'])) {
                $coords = $this->accommodationService->extractLocation($data['google_map_link'] ?? '');
                if ($coords) {
                    $data['latitude'] = $coords['latitude'];
                    $data['longitude'] = $coords['longitude'];
                }
            }

            $accommodation->update($data);
            return new AccommodationResource($accommodation->fresh());
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Accommodation update error: ' . $e->getMessage());

            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Accommodation $accommodation)
    {
        // Check if user is authenticated and is staff
        $user = $request->user();
        if (!$user || !$user->isStaff()) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized. Only staff can delete accommodations.'
            ], 403);
        }

        // Staff can delete their own accommodations

        // Check if staff owns this accommodation
        if ($accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'You can only delete accommodations that you created.'
            ], 403);
        }

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

    public function verify(Request $request, Accommodation $accommodation)
    {
        try {
            // Check if user is authenticated and is admin
            $user = $request->user();
            if (!$user || !$user->isAdmin()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized. Only admins can verify accommodations.'
                ], 403);
            }

            // Toggle verification status
            $accommodation->is_verified = !$accommodation->is_verified;
            $accommodation->save();

            return response()->json([
                'status' => true,
                'message' => $accommodation->is_verified ?
                    'Accommodation verified successfully' :
                    'Accommodation verification removed',
                'accommodation' => new AccommodationResource($accommodation)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to update verification status: ' . $e->getMessage()
            ], 500);
        }
    }
}
