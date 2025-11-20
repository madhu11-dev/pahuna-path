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