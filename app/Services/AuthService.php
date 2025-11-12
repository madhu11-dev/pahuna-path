<?php

namespace App\Services;

use App\Actions\AuthorizationActions\LoginUserAction;
use App\Actions\AuthorizationActions\RegisterUserAction;
use App\Actions\AuthorizationActions\SendVerificationEmailAction;

class AuthService
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected SendVerificationEmailAction $sendVerificationEmailAction,
        protected LoginUserAction $loginUserAction
    ) {}

    public function register(array $validatedData): array
    {
        // 1. Create user
        $user = $this->registerUserAction->handle($validatedData);

        // 2. Send verification email
        $verificationUrl = $this->sendVerificationEmailAction->handle($user);

        return [
            'user' => $user,
            'verification_url' => $verificationUrl
        ];
    }

    public function authLogin(array $validatedData): array
    {
        $user = $this->loginUserAction->handle($validatedData);

        if ($user === null) {
            return ['status' => false, 'message' => 'User not found'];
        }

        if ($user === 'Not_verified') {
            return ['status' => false, 'message' => 'Email not verified'];
        }

        if ($user === 'Invalid_credentials') {
            return ['status' => false, 'message' => 'Invalid credentials'];
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token
        ];
    }
}
