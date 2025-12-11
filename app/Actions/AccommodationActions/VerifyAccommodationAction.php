<?php

namespace App\Actions\AccommodationActions;

use App\Models\Accommodation;
use App\Models\AccommodationVerification;
use Illuminate\Support\Facades\Log;

class VerifyAccommodationAction
{
    public function handle(Accommodation $accommodation): Accommodation
    {
        Log::info('Verifying accommodation', ['accommodation_id' => $accommodation->id]);

        $accommodation->update(['is_verified' => true]);

        // Create or update verification record
        AccommodationVerification::updateOrCreate(
            ['accommodation_id' => $accommodation->id],
            [
                'verified_at' => now(),
                'verification_status' => 'verified'
            ]
        );

        return $accommodation->fresh();
    }
}
