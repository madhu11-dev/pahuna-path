<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraServiceRequest;
use App\Http\Requests\UpdateExtraServiceRequest;
use App\Http\Resources\ExtraServiceResource;
use App\Models\Accommodation;
use App\Models\ExtraService;
use App\Services\ExtraServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExtraServiceController extends Controller
{
    public function __construct(protected ExtraServiceService $extraServiceService) {}

    /**
     * Get all extra services for an accommodation
     */
    public function index($accommodationId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $services = $accommodation->extraServices()->get();

        return response()->json([
            'status' => true,
            'data' => ExtraServiceResource::collection($services)
        ]);
    }

    /**
     * Create new extra service - Owner staff only
     * Middleware: auth.staff
     */
    public function store(StoreExtraServiceRequest $request, $accommodationId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        
        // Authorization: only owner can create services
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();
        $service = $this->extraServiceService->createService($data, $accommodation->id);

        return response()->json([
            'status' => true,
            'message' => 'Service created successfully',
            'data' => new ExtraServiceResource($service)
        ], 201);
    }

    /**
     * Update extra service - Owner staff only
     * Middleware: auth.staff
     */
    public function update(UpdateExtraServiceRequest $request, $accommodationId, $serviceId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $service = ExtraService::where('accommodation_id', $accommodationId)->findOrFail($serviceId);
        
        // Authorization: only owner can update services
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $data = $request->validated();
        $service = $this->extraServiceService->updateService($service, $data);

        return response()->json([
            'status' => true,
            'message' => 'Service updated successfully',
            'data' => new ExtraServiceResource($service)
        ]);
    }

    /**
     * Delete extra service - Owner staff only
     * Middleware: auth.staff
     */
    public function destroy(Request $request, $accommodationId, $serviceId): JsonResponse
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $service = ExtraService::where('accommodation_id', $accommodationId)->findOrFail($serviceId);
        
        // Authorization: only owner can delete services
        if ($accommodation->staff_id !== $request->user()->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $this->extraServiceService->deleteService($service);

        return response()->json([
            'status' => true,
            'message' => 'Service deleted successfully'
        ]);
    }
}

