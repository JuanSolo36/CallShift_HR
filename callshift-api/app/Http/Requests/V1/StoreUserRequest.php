<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'username'              => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->where('company_id', $companyId),
            ],
            'email'                 => [
                'required',
                'string',
                'email:rfc,dns',
                'max:120',
                'unique:users,email',
            ],
            'password'              => [
                'required',
                'string',
                'confirmed',
                Password::min(8)->letters()->mixedCase()->numbers()->symbols(),
            ],
            'password_confirmation' => ['required', 'string'],
            'role_id'               => [
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
            'status'                => ['nullable', 'string', 'in:ACTIVE,INACTIVE,SUSPENDED'],
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'   => 'El nombre de usuario es obligatorio.',
            'username.alpha_dash' => 'El nombre de usuario solo puede contener letras, números, guiones y guiones bajos.',
            'username.unique'     => 'El nombre de usuario ya existe en su empresa.',
            'email.required'      => 'El correo electrónico es obligatorio.',
            'email.email'         => 'Debe proporcionar un formato de correo electrónico válido.',
            'email.unique'        => 'Este correo electrónico ya se encuentra registrado en el sistema.',
            'password.required'   => 'La contraseña inicial es obligatoria.',
            'password.confirmed'  => 'La confirmación de la contraseña no coincide.',
            'role_id.required'    => 'Debe asignar un rol al usuario.',
            'role_id.exists'      => 'El rol seleccionado no es válido o no pertenece a su empresa.',
            'employee_id.exists'  => 'El empleado vinculado no existe en su empresa.',
            'status.in'           => 'El estado debe ser ACTIVE, INACTIVE o SUSPENDED.',
        ];
    }
}
