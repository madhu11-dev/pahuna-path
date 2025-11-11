<?php

namespace App\Services;

use App\Actions\AuthorizationActions\RegisterUserAction;

class AuthService
{
    public function __construct(protected RegisterUserAction $registerUserAction) {}


    public function authRegister($validator)
    {
        $user = $this->registerUserAction->handle($validator);
        return $user;
    }
}
