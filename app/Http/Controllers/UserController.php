<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResources\RegistrationResource;
use App\Services\AuthService;
use Throwable;

class UserController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function register(RegisterRequest $request)
    {
        try {
            $validated = $request->validated();

            $result = $this->authService->register($validated);

            return (new RegistrationResource((object)[
                'user' => $result['user'],
                'verification_url' => $result['verification_url'],
            ]))->response()->setStatusCode(201);
        } catch (Throwable $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
