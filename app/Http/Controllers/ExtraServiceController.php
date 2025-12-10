<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExtraServiceRequest;
use App\Models\Accommodation;
use App\Models\ExtraService;
use Illuminate\Http\Request;

class ExtraServiceController extends Controller
{
    public function index($accommodationId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $services = $accommodation->extraServices()->get();

        return response()->json([
            'status' => true,
            'data' => $services
        ]);
    }

    public function store(StoreExtraServiceRequest $request, $accommodationId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        
        $user = $request->user();
        if (!$user || !$user->isStaff() || $accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $service = $accommodation->extraServices()->create($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Service created successfully',
            'data' => $service
        ], 201);
    }

    public function update(StoreExtraServiceRequest $request, $accommodationId, $serviceId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $service = ExtraService::where('accommodation_id', $accommodationId)->findOrFail($serviceId);
        
        $user = $request->user();
        if (!$user || !$user->isStaff() || $accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $service->update($request->validated());

        return response()->json([
            'status' => true,
            'message' => 'Service updated successfully',
            'data' => $service
        ]);
    }

    public function destroy($accommodationId, $serviceId)
    {
        $accommodation = Accommodation::findOrFail($accommodationId);
        $service = ExtraService::where('accommodation_id', $accommodationId)->findOrFail($serviceId);
        
        $user = request()->user();
        if (!$user || !$user->isStaff() || $accommodation->staff_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $service->delete();

        return response()->json([
            'status' => true,
            'message' => 'Service deleted successfully'
        ]);
    }
}
