<?php

namespace App\Http\Controllers;

use App\Http\Requests\StaffRegisterRequest;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\StaffService;
use Illuminate\Http\Request;
use Throwable;

class StaffController extends Controller
{
    public function __construct(protected StaffService $staffService) {}

    public function register(StaffRegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->staffService->registerStaff($validated);

            return (new RegistrationResource((object)[
                'message' => 'Hotel staff registered successfully! Please verify your email to access your dashboard.'
            ]))->response()->setStatusCode(201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function checkApprovalStatus(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isStaff()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not a staff account'
                ], 403);
            }

            return response()->json([
                'status' => true,
                'email_verified' => $user->email_verified_at !== null,
                'hotel_name' => $user->hotel_name
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function getDashboardData(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isStaff()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not a staff account'
                ], 403);
            }

            if (!$user->email_verified_at) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email first'
                ], 403);
            }

            $dashboardData = $this->staffService->getDashboardData($user);

            return response()->json([
                'success' => true,
                'staff' => $dashboardData['staff'],
                'hotelStats' => $dashboardData['hotelStats']
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function updateProfile(Request $request)
    {
        try {
            $user = $request->user();
            
            if (!$user->isStaff()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Not a staff account'
                ], 403);
            }

            if (!$user->email_verified_at) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email first'
                ], 403);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'phone' => 'sometimes|string|max:20',
                'profile_picture' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            $updatedUser = $this->staffService->updateStaffProfile($user, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Profile updated successfully',
                'user' => $updatedUser
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}