<?php

namespace App\Http\Resources\UserResources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
{
    public static $wrap = false;

    public function toArray(Request $request): array
    {
        return [
            'status' => $this->resource->status,
            'message' => $this->resource->message,
            'user' => $this->resource->user ?? null,
            'token' => $this->resource->token ?? null,
        ];
    }
}
