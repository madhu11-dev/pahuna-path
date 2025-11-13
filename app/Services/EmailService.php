<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\VerifyEmail;
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
            return response()->json(['status' => false, 'message' => 'Email already verified'], 400);
        }

        $user->markEmailAsVerified();
        event(new Verified($user));

        // Set the frontend base URL, adjust this as necessary
        $frontendUrl = env('FRONTEND_URL', 'http://localhost:3000'); // This assumes you're storing it in your .env

        // Redirect to the frontend's login page
        return redirect()->to($frontendUrl . '/login')->with('status', 'Email verified successfully! Please log in.');
    }
}
