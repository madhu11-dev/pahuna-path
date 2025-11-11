<?php

namespace App\Actions\AuthorizationActions;

use App\Models\User;

class RegisterUserAction
{

    public function registerUser($name, $email, $password)
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);

        return $user;
    }
}
