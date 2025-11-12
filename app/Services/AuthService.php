<?php

namespace App\Services;

use App\Actions\AuthorizationActions\RegisterUserAction;
use App\Actions\AuthorizationActions\SendVerificationEmailAction;

class AuthService
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected SendVerificationEmailAction $sendVerificationEmailAction
    ) {}

    public function register(array $validatedData)
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
}