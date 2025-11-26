<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Services\AdminAuthService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class AdminController extends Controller
{
    public function __construct(protected AdminAuthService $adminAuthService)
    {
    }

    // Admin Logout
    public function logout(Request $request)
    {
        if (!$request->user() || $request->user()->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $loggedOut = $this->adminAuthService->adminLogout($request->user());

        return response()->json([
            'status' => $loggedOut,
            'message' => $loggedOut ? 'Logged out successfully' : 'Logout failed'
        ], $loggedOut ? 200 : 400);

    }

    // Get Dashboard Statistics
    public function getDashboardStats(Request $request)
    {
        if (!$request->user() || $request->user()->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $stats = $this->adminAuthService->getDashboardStats();

        return response()->json([
            'status' => true,
            'data' => $stats
        ], 200);

    }

    // Get All Users
    public function getAllUsers(Request $request)
    {
        if (!$request->user() || $request->user()->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $users = $this->adminAuthService->getAllUsers();

        return response()->json([
            'status' => true,
            'users' => $users
        ], 200);

    }

    // Get All Places
    public function getAllPlaces(Request $request)
    {
        if (!$request->user() || $request->user()->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $places = $this->adminAuthService->getAllPlaces();

        return response()->json([
            'status' => true,
            'places' => $places
        ], 200);
    }

    // Get All Hotels/Accommodations
    public function getAllHotels(Request $request)
    {
        // Check if user is admin
        if (!$request->user() || $request->user()->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $hotels = $this->adminAuthService->getAllAccommodations();

        return response()->json([
            'status' => true,
            'hotels' => $hotels
        ], 200);
    }

    // Delete Place
    public function deletePlace(Request $request, Place $place)
    {
        // Check if user is admin
        if (!$request->user() || $request->user()->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $deleted = $this->adminAuthService->deletePlace($place);

        return response()->json([
            'status' => $deleted,
            'message' => $deleted ? 'Place deleted successfully' : 'Failed to delete place'
        ], $deleted ? 200 : 400);
    }

    // Merge Places
    public function mergePlaces(Request $request)
    {
        try {
            // Check if user is admin
            if (!$request->user() || $request->user()->utype !== 'ADM') {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'placeIds' => 'required|array|min:2',
                'placeIds.*' => 'required|integer|exists:places,id',
                'primaryPlaceId' => 'required|integer|exists:places,id',
                'options' => 'required|array',
                'options.keepPrimaryImages' => 'required|boolean',
                'options.keepPrimaryDescription' => 'required|boolean',
                'options.mergeReviews' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            $merged = $this->adminAuthService->mergePlaces(
                $request->placeIds,
                $request->primaryPlaceId,
                $request->options
            );

            return response()->json([
                'status' => $merged['success'],
                'message' => $merged['message'],
                'data' => $merged['data'] ?? null
            ], $merged['success'] ? 200 : 400);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Get current admin info
    public function getAdminInfo(Request $request)
    {
        $user = $request->user();

        // Check if user is admin
        if (!$user || $user->utype !== 'ADM') {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'status' => true,
            'admin' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile_picture_url' => $user->profile_picture_url
            ]
        ], 200);
    }
}