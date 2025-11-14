<?php

namespace App\Actions\AuthorizationActions;


use Illuminate\Auth\Events\Registered;

class SendVerificationEmailAction
{
    public function handle($user)
    {
        $verificationUrl = route('verification.verify', [
            'id' => $user->id,
            'hash' => sha1($user->email),
        ]);

        // Trigger Laravel Registered event (optional)
        event(new Registered($user));

        return $verificationUrl;
    }
}
