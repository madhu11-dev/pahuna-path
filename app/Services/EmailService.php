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

        if ($user->isStaff()) {
            $unverifiedHotel = $user->unverifiedHotel;
            if ($unverifiedHotel && !$unverifiedHotel->is_verified) {
                return redirect($frontendUrl . '/login?status=Email verified successfully! Your hotel registration is pending admin verification.');
            }
        }

        return redirect($frontendUrl . '/login?status=Email verified successfully! Please log in.');
    }

    public function sendStaffApprovalEmail(User $staff)
    {
        return response()->json(['status' => true, 'message' => 'Staff approval email sent']);
    }

    public function sendStaffRejectionEmail(User $staff, string $reason = null)
    {
        return response()->json(['status' => true, 'message' => 'Staff rejection email sent']);
    }
}
