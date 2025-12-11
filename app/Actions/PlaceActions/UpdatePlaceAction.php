<?php

namespace App\Actions\PlaceActions;

use App\Models\Place;

class UpdatePlaceAction
{
    public function handle(Place $place, array $data): Place
    {
        $place->update($data);
        return $place->fresh();
    }
}
