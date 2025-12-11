<?php

namespace App\Actions\AccommodationActions;

use App\Models\Accommodation;
use Illuminate\Support\Facades\Log;

class UpdateAccommodationAction
{
    public function handle(Accommodation $accommodation, array $data): Accommodation
    {
        Log::info('Updating accommodation', [
            'accommodation_id' => $accommodation->id,
            'data' => $data,
        ]);

        $accommodation->update($data);

        return $accommodation->fresh();
    }
}
