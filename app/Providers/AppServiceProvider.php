<?php

namespace App\Providers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
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

        $this->ensurePublicStorageSymlink();
    }

    protected function ensurePublicStorageSymlink(): void
    {
        $publicPath = public_path('storage');
        $storagePath = storage_path('app/public');

        if (!is_dir($storagePath) || is_link($publicPath) || file_exists($publicPath)) {
            return;
        }

        try {
            (new Filesystem())->link($storagePath, $publicPath);
        } catch (\Throwable $e) {
            Log::warning('Failed to create public storage symlink', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
