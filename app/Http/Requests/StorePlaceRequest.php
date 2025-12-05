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
        $imageRules = [
            $this->isMethod('post') ? 'required' : 'sometimes',
            'array',
            'min:1',
            'max:10',
        ];

        return [
            'place_name' => 'required|string|max:255',
            'description' => 'required|string|max:2000', 
            'google_map_link' => 'required|url',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images' => $imageRules,
            'images.*' => 'required|file|mimes:jpeg,jpg,png,gif,webp,avif,heic,heif|max:10240',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $files = $this->file('images');

            if (empty($files)) {
                return;
            }

            if (!is_array($files)) {
                $files = [$files];
            }

            foreach ($files as $index => $file) {
                if (!$file || !$file->isValid()) {
                    $validator->errors()->add("images.$index", 'Invalid file.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'place_name.required' => 'Place name is required.',
            'description.required' => 'Description is required.',
            'description.max' => 'Description cannot exceed 2000 characters.',
            'images.required' => 'At least one image file is required.',
            'images.array' => 'Images must be submitted as an array.',
            'images.min' => 'Please upload at least one image.',
            'images.max' => 'Maximum 10 images allowed.',
            'images.*.mimes' => 'Images must be jpeg, jpg, png, gif, webp, avif, heic, or heif files.',
            'images.*.max' => 'Each image must not exceed 10MB.',
            'google_map_link.required' => 'Google Maps link is required.',
            'google_map_link.url' => 'Google Map link must be a valid URL.',
        ];
    }
}
