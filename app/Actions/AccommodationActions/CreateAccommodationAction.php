<?php

namespace App\Actions\AccommodationActions;

use App\Models\Accommodation;
use Illuminate\Support\Facades\Log;

class CreateAccommodationAction
{
    public function handle(array $data): Accommodation
    {
        Log::info('Creating accommodation', [
            'data' => $data,
            'image_count' => count($data['images'] ?? []),
        ]);

        $accommodation = Accommodation::create($data);

        Log::info('Accommodation created successfully', [
            'accommodation_id' => $accommodation->id,
            'accommodation_name' => $accommodation->name,
        ]);

        return $accommodation;
    }
}
