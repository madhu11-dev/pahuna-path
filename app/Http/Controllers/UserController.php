<?php

namespace App\Http\Controllers;

use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResources\LoginResource;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\AuthService;
use App\Services\FileUploadService;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Requests\ChangePasswordRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UserController extends Controller
{
    public function __construct(protected AuthService $authService, protected FileUploadService $fileUploadService) {}

    // Registration
    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->authService->register($validated);

            return (new RegistrationResource((object)[
            ]))->response()->setStatusCode(201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Login
    public function login(LoginRequest $request)
    {
        try {
            $validated = $request->validated();
            $loginData = $this->authService->authLogin($validated);

            return (new LoginResource((object)$loginData))
                ->response()
                ->setStatusCode($loginData['status'] ? 200 : 401);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        try {
            $validated = $request->validated();
            $response = $this->authService->sendResetPasswordLink($validated);

            return response()->json($response, $response['status'] ? 200 : 400);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        try {
            $validated = $request->validated();
            $response = $this->authService->resetPassword($validated);

            return response()->json($response, $response['status'] ? 200 : 422);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function logout(Request $request)
    {
        try {
            $loggedOut = $this->authService->logout($request->user());
            
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

    /**
     * Get current authenticated user's profile
     */
    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'utype' => $user->utype,
                'profile_picture_url' => $user->profile_picture_url,
            ]
        ], 200);
    }

    /**
     * Update profile (name and profile picture)
     */

public function updateProfile(UpdateProfileRequest $request)
{
    try {
        $user = $request->user();
        $data = $request->validated();

        // Fallback: if validated data is empty (client may send FormData incorrectly), try to read name
        if (empty($data) && $request->has('name')) {
            $data['name'] = $request->input('name');
        }

        // Attach uploaded file (UploadedFile instance) so the action can handle upload
        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture');
        }

        $updatedUser = $this->authService->updateProfile($user, $data);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $updatedUser->id,
                'name' => $updatedUser->name,
                'email' => $updatedUser->email,
                'utype' => $updatedUser->utype,
                'profile_picture_url' => $updatedUser->profile_picture_url,
            ]
        ], 200);
    } catch (Throwable $e) {
        return response()->json([
            'status' => false,
            'message' => $e->getMessage()
        ], 400);
    }
}

    /**
     * Change password (requires current password)
     */
    public function changePassword(ChangePasswordRequest $request)
    {
        try {
            $user = $request->user();

            $current = $request->input('current_password');
            $new = $request->input('new_password');

            if (!Hash::check($current, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Current password is incorrect.'
                ], 403);
            }

            $user->password = Hash::make($new);
            // Invalidate other tokens
            $user->tokens()->delete();
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password changed successfully',
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'utype' => $user->utype,
                    'profile_picture_url' => $user->profile_picture_url,
                ]
            ], 200);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
