<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAccommodationRequest extends FormRequest
{

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $imageRules = [
            'sometimes', // Make images optional for now
            'array',
            'max:5',
        ];
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:hotel,resort,guesthouse,lodge,hostel',
            'description' => 'required|string|max:2000',
            'review' => 'sometimes|numeric|min:0|max:5',
            'google_map_link' => 'required|url',
            'images' => $imageRules,
            'images.*' => 'required|file|mimes:jpeg,jpg,png,gif,webp,avif,heic,heif|max:5120',
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
            'images.required' => 'At least one image file is required.',
            'images.array' => 'Images must be submitted as an array.',
            'images.min' => 'Please upload at least one image.',
            'images.max' => 'Maximum 5 images allowed.',
            'images.*.mimes' => 'Images must be jpeg, jpg, png, gif, webp, avif, heic, or heif files.',
            'images.*.max' => 'Each image must not exceed 10MB.',
            'google_map_link.url' => 'Google Map link must be a valid URL.',
            'type.in' => 'Type must be either restaurant or hotels.',
            'place_id.exists' => 'The selected place does not exist.',
        ];
    }
}
