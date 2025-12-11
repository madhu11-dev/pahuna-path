<?php

namespace App\Actions\StaffActions;

use App\Actions\AuthorizationActions\RegisterUserAction;
use App\Actions\AuthorizationActions\SendVerificationEmailAction;
use App\Models\User;

class RegisterStaffAction
{
    public function __construct(
        protected RegisterUserAction $registerUserAction,
        protected SendVerificationEmailAction $sendVerificationEmailAction
    ) {}

    public function handle(array $validatedData): array
    {
        // Set user type to staff
        $validatedData['utype'] = 'staff';
        $validatedData['is_approved'] = false; // Staff needs admin approval

        // Create user
        $user = $this->registerUserAction->handle($validatedData);

        // Send verification email
        $verificationUrl = $this->sendVerificationEmailAction->handle($user);

        return [
            'user' => $user,
            'verification_url' => $verificationUrl
        ];
    }
}
