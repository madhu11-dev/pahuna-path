<?php

namespace App\Http\Requests;

use App\Rules\UniqueEmailWithoutPlusAddressing;
use Illuminate\Foundation\Http\FormRequest;

class StaffRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                new UniqueEmailWithoutPlusAddressing('users', 'email')
            ],
            'password' => ['required', 'string', 'min:6'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048']
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 6 characters.',
            'profile_picture.image' => 'Profile picture must be an image file.',
            'profile_picture.mimes' => 'Profile picture must be a JPEG, PNG, JPG, or GIF file.',
            'profile_picture.max' => 'Profile picture must be less than 2MB.',
        ];
    }
}
