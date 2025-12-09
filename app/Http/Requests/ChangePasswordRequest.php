<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ChangePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ];
    }

    /**
     * Normalize incoming fields so 'confirmed' validation works even when
     * the client sends other common confirmation field names.
     */
    protected function prepareForValidation(): void
    {
        // If client used 'password_confirmation' or other variants, map it
        // to 'new_password_confirmation' which matches the 'new_password' field.
        if ($this->has('password_confirmation') && !$this->has('new_password_confirmation')) {
            $this->merge(['new_password_confirmation' => $this->input('password_confirmation')]);
        }

        if ($this->has('confirm_password') && !$this->has('new_password_confirmation')) {
            $this->merge(['new_password_confirmation' => $this->input('confirm_password')]);
        }

        if ($this->has('newPasswordConfirmation') && !$this->has('new_password_confirmation')) {
            $this->merge(['new_password_confirmation' => $this->input('newPasswordConfirmation')]);
        }

        // Also accept clients that send 'password' for the new password
        if ($this->has('password') && !$this->has('new_password')) {
            $this->merge(['new_password' => $this->input('password')]);
        }
    }
}
