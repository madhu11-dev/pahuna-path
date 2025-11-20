<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AccommodationResource extends JsonResource
{

    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'images' => $this->images,
            'type' => $this->type,
            'google_map_link' => $this->google_map_link,
            'description' => $this->description,
            'review' => $this->review,
            'user_id' => $this->user_id,
            'place_id' => $this->place_id,
            'lat' => $this->latitude,
            'lng' => $this->longitude,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
