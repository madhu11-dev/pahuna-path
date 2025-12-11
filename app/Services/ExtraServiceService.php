<?php

namespace App\Services;

use App\Actions\ServiceActions\CreateExtraServiceAction;
use App\Actions\ServiceActions\UpdateExtraServiceAction;
use App\Actions\ServiceActions\DeleteExtraServiceAction;
use App\Models\ExtraService;

class ExtraServiceService
{
    public function __construct(
        protected CreateExtraServiceAction $createExtraServiceAction,
        protected UpdateExtraServiceAction $updateExtraServiceAction,
        protected DeleteExtraServiceAction $deleteExtraServiceAction
    ) {}

    public function createService(array $data, int $accommodationId): ExtraService
    {
        return $this->createExtraServiceAction->handle($data, $accommodationId);
    }

    public function updateService(ExtraService $service, array $data): ExtraService
    {
        return $this->updateExtraServiceAction->handle($service, $data);
    }

    public function deleteService(ExtraService $service): void
    {
        $this->deleteExtraServiceAction->handle($service);
    }
}
