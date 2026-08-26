<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePositionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'          => ['required', 'string', 'max:100'],
            'code'          => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('positions', 'code')->where('company_id', $companyId),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'description'   => ['nullable', 'string', 'max:500'],
            'status'        => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'          => 'El nombre del cargo es obligatorio.',
            'code.required'          => 'El código del cargo es obligatorio.',
            'code.unique'            => 'Este código de cargo ya existe en su empresa.',
            'code.alpha_dash'        => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            'department_id.exists'   => 'El departamento seleccionado no existe en su empresa.',
            'status.in'              => 'El estado debe ser ACTIVE o INACTIVE.',
        ];
    }
}
