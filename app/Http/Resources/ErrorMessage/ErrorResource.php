<?php

namespace App\Http\Resources\ErrorMessage;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ErrorResource extends JsonResource
{
    public static $wrap = false;

    public function toArray(Request $request): array
    {
        // Normalize error message from different types of exceptions or responses
        $message = $this->resource;

        if ($this->resource instanceof \Throwable) {
            $message = $this->resource->getMessage();
        } elseif (is_array($this->resource) && isset($this->resource['message'])) {
            $message = $this->resource['message'];
        } elseif (is_object($this->resource) && property_exists($this->resource, 'message')) {
            $message = $this->resource->message;
        }

        return [
            'status' => false,
            'error'  => true,
            'message' => $message ?? 'An unexpected error occurred.',
        ];
    }
}
