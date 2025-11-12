<?php

namespace App\Actions\AuthorizationActions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{

    public function handle(array $validaor)
    {
        $user = User::create([
            'name' => $validaor['name'],
            'email' => $validaor['email'],
            'password' => Hash::make($validaor['password']),
        ]);
        $user->sendEmailVerificationNotification();

        return $user;
    }
}
