<?php

namespace App\Services;

use App\Actions\AuthorizationActions\RegisterUserAction;

class AuthService
{
    public function __construct(protected RegisterUserAction $registerUserAction) {}


    public function authRegister($name, $email, $password): array
    {
        $user = $this->registerUserAction->registerUser($name, $email, $password);
        return [$user];
    }
}
