<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'             => ['required', 'string', 'max:100'],
            'code'             => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('departments', 'code')->where('company_id', $companyId),
            ],
            'cost_center_code' => ['nullable', 'string', 'max:30', 'alpha_dash'],
            'description'      => ['nullable', 'string', 'max:500'],
            'manager_id'       => [
                'nullable',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'status'           => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'El nombre del departamento es obligatorio.',
            'code.required'             => 'El código del departamento es obligatorio.',
            'code.unique'               => 'Este código de departamento ya existe en su empresa.',
            'code.alpha_dash'           => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            'cost_center_code.alpha_dash'=> 'El centro de costo solo puede contener caracteres alfanuméricos y guiones.',
            'manager_id.exists'         => 'El gerente o responsable seleccionado no existe en su empresa.',
            'status.in'                 => 'El estado debe ser ACTIVE o INACTIVE.',
        ];
    }
}
