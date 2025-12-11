<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAccommodationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|in:Hotel,Lodge,Guest House,Resort',
            'description' => 'sometimes|required|string',
            'google_map_link' => 'sometimes|nullable|url',
            'images' => 'sometimes|nullable|array',
            'images.*' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];
    }
}
