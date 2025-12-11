<?php

namespace App\Services;

use App\Actions\AccommodationActions\CreateAccommodationAction;
use App\Actions\AccommodationActions\UpdateAccommodationAction;
use App\Actions\AccommodationActions\DeleteAccommodationAction;
use App\Actions\AccommodationActions\VerifyAccommodationAction;
use App\Models\Accommodation;
use App\Models\AccommodationVerification;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class AccommodationService
{
    public function __construct(
        protected CreateAccommodationAction $createAccommodationAction,
        protected UpdateAccommodationAction $updateAccommodationAction,
        protected DeleteAccommodationAction $deleteAccommodationAction,
        protected VerifyAccommodationAction $verifyAccommodationAction,
        protected FileUploadService $fileUploadService,
        protected KhaltiService $khaltiService
    ) {}

    public function createAccommodation(array $data, User $user): Accommodation
    {
        $data['staff_id'] = $user->id;
        $data['is_verified'] = false;

        // Handle image uploads
        if (isset($data['images']) && request()->hasFile('images')) {
            $data['images'] = $this->fileUploadService->uploadMultiple(
                request()->file('images'),
                'accommodations',
                request()
            );
        }

        // Extract coordinates from Google Maps link
        if (isset($data['google_map_link'])) {
            $coords = $this->extractLocation($data['google_map_link']);
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }
        }

        return $this->createAccommodationAction->handle($data);
    }

    public function updateAccommodation(Accommodation $accommodation, array $data): Accommodation
    {
        // Handle image updates
        if (isset($data['images']) && request()->hasFile('images')) {
            if ($accommodation->images) {
                $this->deleteImages($accommodation->images);
            }

            $data['images'] = $this->fileUploadService->uploadMultiple(
                request()->file('images'),
                'accommodations',
                request()
            );
        }

        // Update coordinates if map link changed
        if (isset($data['google_map_link'])) {
            $coords = $this->extractLocation($data['google_map_link']);
            if ($coords) {
                $data['latitude'] = $coords['latitude'];
                $data['longitude'] = $coords['longitude'];
            }
        }

        return $this->updateAccommodationAction->handle($accommodation, $data);
    }

    public function deleteAccommodation(Accommodation $accommodation): void
    {
        if ($accommodation->images) {
            $this->deleteImages($accommodation->images);
        }

        $this->deleteAccommodationAction->handle($accommodation);
    }

    public function verifyAccommodation(Accommodation $accommodation): Accommodation
    {
        return $this->verifyAccommodationAction->handle($accommodation);
    }

    public function processVerificationPayment(Accommodation $accommodation, User $user, string $token): array
    {
        $verification = $this->khaltiService->verifyPayment($token, 1000); // Rs. 10 = 1000 paisa

        if (!$verification['success']) {
            return [
                'success' => false,
                'message' => $verification['message'] ?? 'Payment verification failed'
            ];
        }

        $paymentData = $verification['data'];

        $verificationRecord = AccommodationVerification::create([
            'accommodation_id' => $accommodation->id,
            'staff_id' => $user->id,
            'verification_fee' => 10.00,
            'payment_method' => 'khalti',
            'transaction_id' => $paymentData['idx'] ?? $token,
            'payment_status' => 'completed',
            'paid_at' => now(),
        ]);

        return [
            'success' => true,
            'message' => 'Payment successful',
            'verification' => $verificationRecord
        ];
    }

    protected function deleteImages(array $imageUrls): void
    {
        foreach ($imageUrls as $imageUrl) {
            $parsedUrl = parse_url($imageUrl);
            if (isset($parsedUrl['path'])) {
                $path = ltrim($parsedUrl['path'], '/');
                if (strpos($path, 'storage/') === 0) {
                    $path = substr($path, 8);
                }
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        }
    }

    protected function extractLocation(string $googleMapLink): ?array
    {
        if (preg_match('/@([-0-9.]+),([-0-9.]+)/', $googleMapLink, $matches)) {
            return ['latitude' => (float)$matches[1], 'longitude' => (float)$matches[2]];
        }

        if (preg_match('/[?&]q=([-0-9.]+),([-0-9.]+)/', $googleMapLink, $matches)) {
            return ['latitude' => (float)$matches[1], 'longitude' => (float)$matches[2]];
        }

        return null;
    }
}


