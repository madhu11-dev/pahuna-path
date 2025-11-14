<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray($request)
    {
        if ($request->isMethod('get')) {
            return [
                'id' => $this->id,
                'place_name' => $this->place_name,
                'images' => $this->images,
                'caption' => $this->caption,
                'review' => $this->review,
                'user_id' => $this->user_id,
            ];
        }

        // Full details for POST/PUT
        return [
            'id' => $this->id,
            'place_name' => $this->place_name,
            'images' => $this->images,
            'google_map_link' => $this->google_map_link,
            'caption' => $this->caption,
            'review' => $this->review,
            'user_id' => $this->user_id,
            'location' => $this->location,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
