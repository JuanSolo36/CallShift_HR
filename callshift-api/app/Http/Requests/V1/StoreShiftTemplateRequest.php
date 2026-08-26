<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftTemplateRequest extends FormRequest
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
                Rule::unique('shift_templates', 'code')->where('company_id', $companyId),
            ],
            'department_id'    => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'position_id'      => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')->where('company_id', $companyId),
            ],
            'shift_pattern_id' => [
                'nullable',
                'integer',
                Rule::exists('shift_patterns', 'id')->where('company_id', $companyId),
            ],
            'description'      => ['nullable', 'string', 'max:500'],
            'status'           => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
            'metadata'         => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'             => 'El nombre de la plantilla es obligatorio.',
            'code.required'             => 'El código de la plantilla es obligatorio.',
            'code.unique'               => 'Ya existe una plantilla con este código en la empresa.',
            'shift_pattern_id.exists'   => 'El patrón de turno asociado no existe o pertenece a otra empresa.',
            'department_id.exists'      => 'El departamento seleccionado no existe o pertenece a otra empresa.',
            'position_id.exists'        => 'El cargo seleccionado no existe o pertenece a otra empresa.',
        ];
    }
}
