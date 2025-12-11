<?php

namespace App\Http\Controllers;

use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Http\Resources\UserResources\LoginResource;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    /**
     * Register new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return (new RegistrationResource((object)[]))->response()->setStatusCode(201);
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $loginData = $this->authService->authLogin($request->validated());

        return (new LoginResource((object)$loginData))
            ->response()
            ->setStatusCode($loginData['status'] ? 200 : 401);
    }

    /**
     * Forgot password - send reset link
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $response = $this->authService->sendResetPasswordLink($request->validated());

        return response()->json($response, $response['status'] ? 200 : 400);
    }

    /**
     * Reset password with token
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $response = $this->authService->resetPassword($request->validated());

        return response()->json($response, $response['status'] ? 200 : 422);
    }

    /**
     * Logout user
     * Middleware: auth:sanctum
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json([
            'status' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * Get current user profile
     * Middleware: auth:sanctum
     */
    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'status' => true,
            'data' => new UserResource($request->user())
        ]);
    }

    /**
     * Update user profile
     * Middleware: auth:sanctum
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Attach uploaded file if present
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture');
        }

        $updatedUser = $this->authService->updateProfile($request->user(), $data);

        return response()->json([
            'status' => true,
            'data' => new UserResource($updatedUser)
        ]);
    }

    /**
     * Change password
     * Middleware: auth:sanctum
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        // Verify current password
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.'
            ], 403);
        }

        // Update password and invalidate other tokens
        $user->password = Hash::make($request->new_password);
        $user->tokens()->delete();
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully',
            'data' => new UserResource($user)
        ]);
    }
}
