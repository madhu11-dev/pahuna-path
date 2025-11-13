<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blueprint::macro('point', function ($column, $srid = 4326) {
            $this->addColumn('geometry', $column, [
                'type' => 'POINT',
                'srid' => $srid,
            ]);
        });
    }
}
