<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Events\Verified;

class EmailService
{
    public function sendMail(User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json(['status' => false, 'message' => 'Email already verified'], 400);
        }

        $verificationUrl = URL::temporarySignedRoute(
            'verify.email',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1($user->email)]
        );

        // Mail::to($user->email)->send(new VerifyEmail($user, $verificationUrl));

        return response()->json(['status' => true, 'message' => 'Verification email sent']);
    }

    public function verification($id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals(sha1($user->email), $hash)) {
            return response()->json(['status' => false, 'message' => 'Invalid verification link'], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONTEND_URL') . '/login?status=Email already verified.');
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000'); 

        return redirect($frontendUrl . '/login?status=Email verified successfully! Please log in.');
    }
}
