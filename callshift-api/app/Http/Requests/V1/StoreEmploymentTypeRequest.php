<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmploymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'                 => ['required', 'string', 'max:60'],
            'code'                 => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('employment_types', 'code')->where('company_id', $companyId),
            ],
            'default_weekly_hours' => ['required', 'numeric', 'min:1.0', 'max:60.0'],
            'description'          => ['nullable', 'string', 'max:500'],
            'status'               => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                 => 'El nombre del tipo de contrato es obligatorio.',
            'code.required'                 => 'El código del tipo de contrato es obligatorio.',
            'code.unique'                   => 'Este código de contrato ya existe en su empresa.',
            'code.alpha_dash'               => 'El código solo puede contener letras, números, guiones y guiones bajos.',
            'default_weekly_hours.required' => 'Las horas base semanales son obligatorias.',
            'default_weekly_hours.numeric'  => 'Las horas base semanales deben ser un número válido.',
            'default_weekly_hours.min'      => 'La jornada semanal debe ser de al menos 1 hora.',
            'default_weekly_hours.max'      => 'La jornada semanal no puede exceder las 60 horas.',
            'status.in'                     => 'El estado debe ser ACTIVE o INACTIVE.',
        ];
    }
}
