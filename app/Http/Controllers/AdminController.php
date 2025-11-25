<?php

namespace App\Http\Controllers;


class AdminController extends Controller
{
    public function __construct(protected AdminAuthService $adminAuthService) {}
        // Admin Logout
    public function logout(Request $request)
    {
        try {
            // Check if user is admin
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
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

        // Get Dashboard Statistics
    public function getDashboardStats(Request $request)
    {
        try {
            // Check if user is admin
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
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

        // Get All Users
    public function getAllUsers(Request $request)
    {
        try {
            // Check if user is admin
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
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

        // Get All Places
    public function getAllPlaces(Request $request)
    {
        try {
            // Check if user is admin
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
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

        // Get All Hotels/Accommodations
    public function getAllHotels(Request $request)
    {
        try {
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
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

}