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

        // Prepare user data
        $userData = [
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'profile_picture' => $profilePicturePath,
            'utype' => $validatedData['utype'] ?? 'USR',
        ];

        // Add optional fields if they exist
        if (isset($validatedData['hotel_name'])) {
            $userData['hotel_name'] = $validatedData['hotel_name'];
        }
        if (isset($validatedData['phone'])) {
            $userData['phone'] = $validatedData['phone'];
        }
        if (isset($validatedData['is_approved'])) {
            $userData['is_approved'] = $validatedData['is_approved'];
        } elseif (isset($validatedData['utype']) && $validatedData['utype'] === 'STF') {
            $userData['is_approved'] = false; // Staff default to not approved
        }

        $user = User::create($userData);
        
        $user->sendEmailVerificationNotification();

        return $user;
    }
}
