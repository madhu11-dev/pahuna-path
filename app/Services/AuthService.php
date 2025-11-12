<?php

namespace App\Services;

use App\Actions\AuthorizationActions\RegisterUserAction;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    public function __construct(protected RegisterUserAction $registerUserAction) {}


    public function authRegister($validator)
    {
        return $this->registerUserAction->handle($validator);
    }
    public function authLogin(array $credentials)
    {
        if (!Auth::attempt($credentials)) {
            return null;
        }

        $user = Auth::user();

        if (!$user->hasVerifiedEmail()) {
            return 'unverified';
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }
}
