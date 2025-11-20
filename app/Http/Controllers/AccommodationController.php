<?php

namespace App\Http\Controllers;

use App\Services\AccommodationService;

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