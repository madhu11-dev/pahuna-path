<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResources\LoginResource;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\AuthService;
use Throwable;

class UserController extends Controller
{
    public function __construct(protected AuthService $authService) {}

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
}
