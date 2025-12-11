<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RoomResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'accommodation_id' => $this->accommodation_id,
            'room_name' => $this->room_name,
            'room_type' => $this->room_type,
            'has_ac' => (bool) $this->has_ac,
            'description' => $this->description,
            'capacity' => (int) $this->capacity,
            'total_rooms' => (int) $this->total_rooms,
            'base_price' => (float) $this->base_price,
            'price_per_night' => (float) $this->base_price, // Alias for backward compatibility
            'images' => $this->images,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
