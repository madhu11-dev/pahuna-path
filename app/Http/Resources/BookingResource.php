<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'user' => new UserResource($this->whenLoaded('user')),
            'accommodation' => new AccommodationResource($this->whenLoaded('accommodation')),
            'room' => new RoomResource($this->whenLoaded('room')),
            'services' => BookingServiceResource::collection($this->whenLoaded('services')),
            'check_in_date' => $this->check_in_date,
            'check_out_date' => $this->check_out_date,
            'check_in_time' => $this->check_in_time,
            'check_out_time' => $this->check_out_time,
            'number_of_rooms' => (int) $this->number_of_rooms,
            'number_of_guests' => (int) $this->number_of_guests,
            'total_nights' => (int) $this->total_nights,
            'room_subtotal' => (float) $this->room_subtotal,
            'services_subtotal' => (float) $this->services_subtotal,
            'total_amount' => (float) $this->total_amount,
            'payment_status' => $this->payment_status,
            'payment_method' => $this->payment_method,
            'booking_status' => $this->booking_status,
            'special_requests' => $this->special_requests,
            'cancellation_reason' => $this->cancellation_reason,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
