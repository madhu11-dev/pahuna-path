<?php

namespace App\Services;

use App\Actions\AuthorizationActions\LoginUserAction;
use App\Actions\AuthorizationActions\RegisterUserAction;
use App\Actions\AuthorizationActions\SendVerificationEmailAction;
use App\Actions\AuthorizationActions\UpdateUserProfileAction;
use App\Mail\ResetPasswordMail;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected SendVerificationEmailAction $sendVerificationEmailAction,
        protected LoginUserAction $loginUserAction
    ) {}

    /**
     * Update user profile using action.
     *
     * @param \App\Models\User $user
     * @param array $data
     * @return \App\Models\User
     */
    public function updateProfile($user, array $data)
    {
        // Lazily resolve UpdateUserProfileAction from container if not injected
        $action = app(UpdateUserProfileAction::class);

        return $action->handle($user, $data);
    }

    public function register(array $validatedData): array
    {
        // 1. Create user
        $user = $this->registerUserAction->handle($validatedData);

        // 2. Send verification email
        $verificationUrl = $this->sendVerificationEmailAction->handle($user);

        return [
            'user' => $user,
            'verification_url' => $verificationUrl
        ];
    }

    public function authLogin(array $validatedData): array
    {
        $user = $this->loginUserAction->handle($validatedData);

        if ($user === null) {
            return ['status' => false, 'message' => 'User not found'];
        }

        if ($user === 'Not_verified') {
            return ['status' => false, 'message' => 'Email not verified'];
        }

        if ($user === 'Not_approved') {
            return ['status' => false, 'message' => 'Your staff account is pending admin approval'];
        }

        if ($user === 'Invalid_credentials') {
            return ['status' => false, 'message' => 'Invalid credentials'];
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'Login successfully',
            'user' => $user,
            'token' => $token
        ];
    }

    public function sendResetPasswordLink(array $validatedData): array
    {
        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            return [
                'status' => true,
                'message' => 'If the email exists, a reset link has been sent.',
            ];
        }

        $token = Password::createToken($user);
        $frontendUrl = rtrim(config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $resetUrl = $frontendUrl . '/reset-password?token=' . urlencode($token) . '&email=' . urlencode($user->email);

        Mail::to($user->email)->send(new ResetPasswordMail($resetUrl));

        return [
            'status' => true,
            'message' => 'Password reset link sent successfully.',
        ];
    }

    public function resetPassword(array $validatedData): array
    {
        $status = Password::reset(
            [
                'email' => $validatedData['email'],
                'token' => $validatedData['token'],
                'password' => $validatedData['password'],
                'password_confirmation' => $validatedData['password_confirmation'],
            ],
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return [
                'status' => true,
                'message' => 'Password updated successfully.',
            ];
        }

        return [
            'status' => false,
            'message' => __($status),
        ];
    }

    public function logout($user): bool
    {
        if ($user && $user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
            return true;
        }
        
        return false;
    }
}
