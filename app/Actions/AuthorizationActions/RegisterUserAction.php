<?php

namespace App\Actions\AuthorizationActions;

use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Support\Facades\Hash;

class RegisterUserAction
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    public function handle(array $validatedData)
    {
        $profilePicturePath = null;
        
        // Handle profile picture upload if provided
        if (isset($validatedData['profile_picture']) && $validatedData['profile_picture']) {
            $profilePicturePath = $this->fileUploadService->uploadProfilePicture(
                $validatedData['profile_picture']
            );
        }

        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'profile_picture' => $profilePicturePath,
        ]);
        
        $user->sendEmailVerificationNotification();

        return $user;
    }
}
