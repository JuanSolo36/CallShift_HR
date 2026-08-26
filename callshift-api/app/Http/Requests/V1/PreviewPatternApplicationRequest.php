<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewPatternApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'pattern_id'        => [
                'required',
                'integer',
                Rule::exists('shift_patterns', 'id')->where('company_id', $companyId)->where('status', 'ACTIVE'),
            ],
            'employee_ids'      => ['required', 'array', 'min:1'],
            'employee_ids.*'    => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId)->where('status', 'ACTIVE'),
            ],
            'start_offset_day'  => ['nullable', 'integer', 'min:1', 'max:365'],
            'start_date'        => ['nullable', 'date_format:Y-m-d'],
            'end_date'          => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'override_existing' => ['nullable', 'boolean'],
            'lock_version'      => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'pattern_id.required'        => 'Debe seleccionar un patrón de turno.',
            'pattern_id.exists'          => 'El patrón de turno seleccionado no existe, está inactivo o pertenece a otra empresa.',
            'employee_ids.required'      => 'Debe seleccionar al menos un colaborador.',
            'employee_ids.min'           => 'Debe seleccionar al menos un colaborador.',
            'employee_ids.*.exists'      => 'Uno o más colaboradores seleccionados no existen, están inactivos o pertenecen a otra empresa.',
            'start_offset_day.min'       => 'El offset inicial debe ser mayor o igual a 1.',
            'lock_version.required'      => 'El lock_version actual de la versión es obligatorio.',
        ];
    }
}
