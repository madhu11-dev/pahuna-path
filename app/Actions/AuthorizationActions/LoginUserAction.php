<?php

namespace App\Actions\AuthorizationActions;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginUserAction
{
    /**
     * Authenticate the user based on validated input
     *
     * @param array $validatedData ['email' => string, 'password' => string]
     * @return User|string|null
     */
    public function handle(array $validatedData)
    {
        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            return null; // User not found
        }

        if (!$user->hasVerifiedEmail()) {
            return 'Not_verified'; // Email not verified
        }

        // Staff can login regardless of approval status

        if (!Hash::check($validatedData['password'], $user->password)) {
            return 'Invalid_credentials'; // Wrong password
        }

        return $user;
    }
}
