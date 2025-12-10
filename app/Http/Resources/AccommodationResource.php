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
            'staff_id' => $this->staff_id,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_verified' => $this->is_verified,
            'average_rating' => $this->average_rating,
            'review_count' => $this->review_count,
            'reviews' => $this->whenLoaded('reviews'),
            'has_paid_verification' => $this->hasVerificationPayment(),
            'verification' => $this->whenLoaded('verification'),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    public function with($request)
    {
        return [
            'status' => true,
            'message' => 'Accommodation saved successfully'
        ];
    }
}
