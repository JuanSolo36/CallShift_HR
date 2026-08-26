<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\EmploymentType;

class UpdateEmploymentTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('employment_type');
        $id = $target instanceof EmploymentType ? $target->id : ($this->route('id') ?? $target);
        $companyId = $this->user()?->company_id;

        return [
            'name'                 => ['sometimes', 'required', 'string', 'max:60'],
            'code'                 => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('employment_types', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($id),
            ],
            'default_weekly_hours' => ['sometimes', 'required', 'numeric', 'min:1.0', 'max:60.0'],
            'description'          => ['nullable', 'string', 'max:500'],
            'status'               => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'              => 'Este código de tipo de contrato ya está en uso en su empresa.',
            'default_weekly_hours.min' => 'La jornada semanal debe ser de al menos 1 hora.',
            'default_weekly_hours.max' => 'La jornada semanal no puede exceder las 60 horas.',
        ];
    }
}
