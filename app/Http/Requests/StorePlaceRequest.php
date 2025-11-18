<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'place_name' => 'required|string|max:255',
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'required|url',
            'caption' => 'required|string',
            'review' => 'required|numeric|min:0|max:5',
            'google_map_link' => 'required|url',
        ];
    }

    public function messages(): array
    {
        return [
            'images.*.url' => 'Each image must be a valid URL.',
            'google_map_link.url' => 'Google Map link must be a valid URL.',
        ];
    }
}
