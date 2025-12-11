<?php

namespace App\Actions\PlaceActions;

use App\Models\Place;
use Illuminate\Support\Facades\Log;

class CreatePlaceAction
{
    public function handle(array $data): Place
    {
        Log::info('Creating place', ['data' => $data]);

        $place = Place::create($data);

        Log::info('Place created successfully', [
            'place_id' => $place->id,
            'place_name' => $place->place_name,
        ]);

        return $place;
    }
}
