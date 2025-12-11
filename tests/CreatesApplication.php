<?php

namespace Tests;

use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        // Load environment variables from .env.testing if it exists
        if (file_exists(dirname(__DIR__) . '/.env.testing')) {
            (new LoadEnvironmentVariables())->bootstrap($app);
        }

        return $app;
    }
}
