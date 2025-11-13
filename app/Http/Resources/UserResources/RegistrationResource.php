<?php

namespace App\Http\Resources\UserResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public static $wrap = false;

    public function toArray(Request $request): array
    {
        return [
            'status' => true,
            'message' => 'User registered successfully! Please verify email',
            // 'user' => $this->resource->user,
            // 'verification_url' => $this->resource->verification_url,
        ];
    }
}
