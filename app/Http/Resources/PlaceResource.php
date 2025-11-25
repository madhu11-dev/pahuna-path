<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PlaceResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'place_name' => $this->place_name,
            'description' => $this->description,
            'images' => $this->images,
            'google_map_link' => $this->google_map_link,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'is_merged' => $this->is_merged,
            'merged_from_ids' => $this->merged_from_ids,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'profile_picture_url' => $this->user->profile_picture_url ?? '/images/default-profile.png',
            ] : null,
            'reviews' => PlaceReviewResource::collection($this->whenLoaded('reviews')),
            'average_rating' => $this->average_rating ?? 0,
            'review_count' => $this->review_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
