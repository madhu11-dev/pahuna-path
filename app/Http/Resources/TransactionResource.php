<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking' => new BookingResource($this->whenLoaded('booking')),
            'user' => new UserResource($this->whenLoaded('user')),
            'amount' => $this->amount,
            'payment_method' => $this->payment_method,
            'transaction_type' => $this->transaction_type,
            'status' => $this->status,
            'transaction_id' => $this->transaction_id,
            'payment_details' => $this->payment_details,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
