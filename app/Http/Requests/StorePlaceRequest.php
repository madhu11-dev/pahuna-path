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
        $rules = [
            'place_name' => 'required|string|max:255',
            'caption' => 'required|string',
            'review' => 'required|numeric|min:0|max:5',
            'google_map_link' => 'required|url',
        ];

        if ($this->isMethod('post')) {
            if (!$this->hasFile('images')) {
                $rules['images'] = 'required';
            }
        }

        return $rules;
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if ($this->hasFile('images')) {
                $files = $this->file('images');
                
                if (!is_array($files)) {
                    $files = [$files];
                }

                if (empty($files)) {
                    $validator->errors()->add('images', 'At least one image file is required.');
                    return;
                }

                if (count($files) > 5) {
                    $validator->errors()->add('images', 'Maximum 5 images allowed.');
                    return;
                }

                foreach ($files as $index => $file) {
                    if (!$file->isValid()) {
                        $validator->errors()->add("images.$index", 'Invalid file.');
                        continue;
                    }

                    $mimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file->getMimeType(), $mimeTypes)) {
                        $validator->errors()->add("images.$index", 'File must be an image (jpeg, jpg, png, gif, or webp).');
                    }

                    if ($file->getSize() > 10240 * 1024) {
                        $validator->errors()->add("images.$index", 'Each image must not exceed 10MB.');
                    }
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'images.required' => 'At least one image file is required.',
            'images.*.image' => 'Each file must be a valid image.',
            'images.*.mimes' => 'Images must be in jpeg, jpg, png, gif, or webp format.',
            'images.*.max' => 'Each image must not exceed 10MB.',
            'google_map_link.url' => 'Google Map link must be a valid URL.',
        ];
    }
}
