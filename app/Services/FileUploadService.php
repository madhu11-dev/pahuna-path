<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload profile picture and return the file path
     *
     * @param UploadedFile $file
     * @param string $directory
     * @return string|null
     */
    public function uploadProfilePicture(UploadedFile $file, string $directory = 'profile-pictures'): ?string
    {
            // Generate unique filename
            $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
            
            // Store file in public disk
            $path = $file->storeAs($directory, $filename, 'public');
            
            return 'storage/' . $path;
    }

    /**
     * Delete profile picture file
     *
     * @param string $filePath
     * @return bool
     */
    public function deleteProfilePicture(string $filePath): bool
    {
        try {
            if (Str::startsWith($filePath, 'storage/')) {
                $diskPath = Str::after($filePath, 'storage/');
                return Storage::disk('public')->delete($diskPath);
            }
            return false;
        } catch (\Exception $e) {
            \Log::error('Profile picture deletion failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate image file
     *
     * @param UploadedFile $file
     * @return array
     */
    public function validateImage(UploadedFile $file): array
    {
        $allowedMimes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
        $maxSize = 2048; // 2MB in KB

        $errors = [];

        if (!in_array($file->getMimeType(), $allowedMimes)) {
            $errors[] = 'Profile picture must be a valid image file (JPEG, PNG, JPG, GIF).';
        }

        if ($file->getSize() > ($maxSize * 1024)) {
            $errors[] = 'Profile picture must be less than 2MB.';
        }

        return $errors;
    }
}