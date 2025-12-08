<?php

namespace App\Actions\AuthorizationActions;

use App\Models\User;
use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Throwable;

class UpdateUserProfileAction
{
    public function __construct(protected FileUploadService $fileUploadService) {}

    /**
     * Update user profile.
     *
     * @param User $user
     * @param array $data  // may contain 'name' and/or 'profile_picture' (UploadedFile)
     * @return User
     */
    public function handle(User $user, array $data): User
    {
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        // If profile picture is provided as UploadedFile
        if (isset($data['profile_picture']) && $data['profile_picture'] instanceof UploadedFile) {
            $file = $data['profile_picture'];
            // Upload new picture
            $path = $this->fileUploadService->uploadProfilePicture($file, 'profile_pictures');

            // Delete old picture if present
            if ($user->profile_picture) {
                $this->fileUploadService->deleteProfilePicture($user->profile_picture);
            }

            $user->profile_picture = $path;
        }

        try {
            $user->save();
        } catch (Throwable $e) {
            throw $e;
        }

        return $user;
    }
}
