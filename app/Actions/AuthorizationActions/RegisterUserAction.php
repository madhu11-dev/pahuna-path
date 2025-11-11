<?php

namespace App\Actions\AuthorizationActions;

use App\Models\User;

class RegisterUserAction
{

    public function handle($validaor)
    {
        $name = $validaor['name'];
        $email = $validaor['email'];
        $password = $validaor['password'];
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password
        ]);

        return $user;
    }
}
