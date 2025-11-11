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
            'status' => false,
            'message' => 'User registered successfully!',
            'user' => $this->resource->user,
            'token' => $this->resource->user->createToken('API TOKEN')->plainTextToken,
            'verifyUrl' => $this->resource->verificationUrl

        ];
    }
}
