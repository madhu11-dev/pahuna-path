<?php

namespace App\Actions\ServiceActions;

use App\Models\ExtraService;

class UpdateExtraServiceAction
{
    public function handle(ExtraService $service, array $data): ExtraService
    {
        $service->update($data);
        return $service->fresh();
    }
}
