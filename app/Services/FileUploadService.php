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

    /**
     * Upload multiple files and return array of URLs
     *
     * @param array $files
     * @param string $directory
     * @param mixed $request
     * @return array
     */
    public function uploadMultiple(array $files, string $directory, $request): array
    {
        $urls = [];

        foreach ($files as $file) {
            if (!$file || !$file->isValid()) {
                \Log::warning('Invalid file upload skipped', [
                    'file' => $file ? $file->getClientOriginalName() : null,
                ]);
                continue;
            }

            try {
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }

                $path = $file->store($directory, 'public');

                if (!$path) {
                    \Log::error('Failed to store file: ' . $file->getClientOriginalName());
                    continue;
                }

                $urls[] = $this->buildPublicStorageUrl($request, $path);
            } catch (\Throwable $e) {
                \Log::error('Error storing file: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return $urls;
    }

    /**
     * Build public storage URL
     *
     * @param mixed $request
     * @param string $path
     * @return string
     */
    private function buildPublicStorageUrl($request, string $path): string
    {
        return $request->getSchemeAndHttpHost() . '/storage/' . $path;
    }
}