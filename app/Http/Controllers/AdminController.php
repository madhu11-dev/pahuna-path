<?php

namespace App\Http\Controllers;

use App\Http\Requests\MergePlacesRequest;
use App\Models\Place;
use App\Models\User;
use App\Services\AdminAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function __construct(protected AdminAuthService $adminAuthService) {}

    /**
     * Admin Logout
     * Middleware: auth.admin
     */
    public function logout(Request $request): JsonResponse
    {
        $this->adminAuthService->adminLogout($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get Dashboard Statistics
     * Middleware: auth.admin
     */
    public function getDashboardStats(): JsonResponse
    {
        $stats = $this->adminAuthService->getDashboardStats();

        return response()->json([
            'status' => true,
            'data' => $stats
        ]);
    }

    /**
     * Get All Users
     * Middleware: auth.admin
     */
    public function getAllUsers(): JsonResponse
    {
        $users = $this->adminAuthService->getAllUsers();

        return response()->json([
            'status' => true,
            'users' => $users
        ]);
    }

    /**
     * Get All Places
     * Middleware: auth.admin
     */
    public function getAllPlaces(): JsonResponse
    {
        $places = $this->adminAuthService->getAllPlaces();

        return response()->json([
            'status' => true,
            'places' => $places
        ]);
    }

    /**
     * Delete Place
     * Middleware: auth.admin
     */
    public function deletePlace(Place $place): JsonResponse
    {
        $deleted = $this->adminAuthService->deletePlace($place);

        return response()->json([
            'status' => $deleted,
            'message' => $deleted ? 'Place deleted successfully' : 'Failed to delete place'
        ], $deleted ? 200 : 400);
    }

    /**
     * Merge Places
     * Middleware: auth.admin
     */
    public function mergePlaces(MergePlacesRequest $request): JsonResponse
    {
        $merged = $this->adminAuthService->mergePlaces(
            $request->placeIds,
            $request->mergeData
        );

        return response()->json([
            'status' => $merged['success'],
            'message' => $merged['message'],
            'data' => $merged['data'] ?? null
        ], $merged['success'] ? 200 : 400);
    }

    /**
     * Delete User
     * Middleware: auth.admin
     */
    public function deleteUser(User $user): JsonResponse
    {
        // Prevent admin from deleting other admins
        if ($user->utype === 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Cannot delete admin users'
            ], 403);
        }

        $deleted = $this->adminAuthService->deleteUser($user);

        return response()->json([
            'status' => $deleted,
            'message' => $deleted ? 'User deleted successfully' : 'Failed to delete user'
        ], $deleted ? 200 : 400);
    }

    /**
     * Get current admin info
     * Middleware: auth.admin
     */
    public function getAdminInfo(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'admin' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture_url' => $user->profile_picture_url
            ]
        ]);
    }

    /**
     * Toggle place verification status
     * Middleware: auth.admin
     */
    public function toggleVerifyPlace(Place $place): JsonResponse
    {
        $place->is_verified = !$place->is_verified;
        $place->save();

        return response()->json([
            'status' => true,
            'message' => $place->is_verified ? 'Place verified successfully' : 'Place verification removed',
            'is_verified' => $place->is_verified
        ]);
    }

    /**
     * Get All Staff
     * Middleware: auth.admin
     */
    public function getAllStaff(): JsonResponse
    {
        $result = $this->adminAuthService->getAllStaff();

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    /**
     * Get Pending Accommodations
     * Middleware: auth.admin
     */
    public function getPendingAccommodations(): JsonResponse
    {
        $result = $this->adminAuthService->getAllStaff();

        return response()->json([
            'status' => $result['success'],
            'data' => $result['data'] ?? null,
            'message' => $result['message'] ?? 'Staff list retrieved successfully'
        ], $result['success'] ? 200 : 400);
    }
}