<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected function mapPlaceRoutes()
    {
        Route::middleware('api') // or 'web'
             ->group(base_path('routes/PlaceRoutes.php'));
    }

    public function boot()
    {
        parent::boot();

        $this->mapPlaceRoutes(); // call it here
    }
}
