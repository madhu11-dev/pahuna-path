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
            $this->isMethod('post') ? 'required' : 'sometimes',
            'array',
            'min:1',
            'max:5',
        ];
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:restaurant,hotels',
            'description' => 'required|string',
            'review' => 'required|numeric|min:0|max:5',
            'google_map_link' => 'required|url',
            'place_id' => 'required|exists:places,id',
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
}
