<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ChangePasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for password change.
     */
    public function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'password'         => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'revoke_other_sessions' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'current_password.required' => 'La contraseña actual es obligatoria.',
            'password.required'         => 'La nueva contraseña es obligatoria.',
            'password.confirmed'        => 'La confirmación de la nueva contraseña no coincide.',
            'password.min'              => 'La nueva contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
