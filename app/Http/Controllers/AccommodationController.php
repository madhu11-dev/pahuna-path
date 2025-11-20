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
}