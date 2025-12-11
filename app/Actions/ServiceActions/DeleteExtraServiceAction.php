<?php

namespace App\Actions\ServiceActions;

use App\Models\ExtraService;

class DeleteExtraServiceAction
{
    public function handle(ExtraService $service): void
    {
        $service->delete();
    }
}
