<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|numeric|min:0|max:5',
            'comment' => 'nullable|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'rating.required' => 'Rating is required.',
            'rating.numeric' => 'Rating must be a number.',
            'rating.min' => 'Rating must be at least 0.',
            'rating.max' => 'Rating cannot be more than 5.',
            'comment.max' => 'Comment cannot exceed 1000 characters.',
        ];
    }
}