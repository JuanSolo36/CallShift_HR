<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateShiftTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId  = $this->user()?->company_id;
        $templateId = $this->route('template') ?? $this->route('shift_template') ?? $this->route('id');

        return [
            'name'             => ['sometimes', 'required', 'string', 'max:100'],
            'code'             => [
                'sometimes',
                'required',
                'string',
                'max:30',
                Rule::unique('shift_templates', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($templateId),
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
            'status'           => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE'],
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
        ];
    }
}
