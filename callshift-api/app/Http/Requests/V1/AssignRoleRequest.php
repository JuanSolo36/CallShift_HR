<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(function ($query) use ($companyId) {
                    $query->whereNull('company_id')
                          ->orWhere('company_id', $companyId);
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role_id.required' => 'El rol a asignar es obligatorio.',
            'role_id.exists'   => 'El rol especificado no existe o no pertenece a su empresa.',
        ];
    }
}
