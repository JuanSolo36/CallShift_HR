<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use App\Models\User;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $targetUser = $this->route('user');
        $userId = $targetUser instanceof User ? $targetUser->id : ($this->route('id') ?? $targetUser);
        $companyId = $this->user()?->company_id;

        return [
            'username'              => [
                'sometimes',
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')
                    ->where('company_id', $companyId)
                    ->ignore($userId),
            ],
            'email'                 => [
                'sometimes',
                'required',
                'string',
                'email:rfc,dns',
                'max:120',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password'              => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'password_confirmation' => ['required_with:password', 'nullable', 'string'],
            'role_id'               => [
                'sometimes',
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(function ($query) use ($companyId) {
                    $query->whereNull('company_id')
                          ->orWhere('company_id', $companyId);
                }),
            ],
            'employee_id'           => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'status'                => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE,SUSPENDED'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique'    => 'Este nombre de usuario ya está en uso en su empresa.',
            'email.unique'       => 'Este correo electrónico ya está en uso por otro usuario.',
            'password.confirmed' => 'La confirmación de la nueva contraseña no coincide.',
            'role_id.exists'     => 'El rol seleccionado no es válido o no pertenece a su empresa.',
            'employee_id.exists' => 'El empleado vinculado no existe en su empresa.',
        ];
    }
}
