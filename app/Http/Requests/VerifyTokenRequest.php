<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VerifyTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'auth_token'    => 'auth_token',
        ];
    }

    public function messages(): array
    {
        return [
            'auth_token.required' => 'Auth token missing',
        ];
    }
}
