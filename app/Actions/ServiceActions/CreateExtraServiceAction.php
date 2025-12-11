<?php

namespace App\Actions\ServiceActions;

use App\Models\ExtraService;

class CreateExtraServiceAction
{
    public function handle(array $data, int $accommodationId): ExtraService
    {
        $data['accommodation_id'] = $accommodationId;
        return ExtraService::create($data);
    }
}
