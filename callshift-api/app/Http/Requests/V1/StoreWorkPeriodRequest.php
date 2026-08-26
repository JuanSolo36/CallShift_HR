<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkPeriodRequest extends FormRequest
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
            'period_type'   => ['nullable', 'string', 'in:WEEKLY,BIWEEKLY,MONTHLY,CUSTOM'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'start_date'    => ['required', 'date_format:Y-m-d'],
            'end_date'      => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status'        => ['nullable', 'string', 'in:DRAFT,GENERATED,REVIEW,PUBLISHED,CLOSED'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'El nombre del periodo de trabajo es obligatorio.',
            'start_date.required'        => 'La fecha de inicio es obligatoria.',
            'start_date.date_format'     => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'end_date.required'          => 'La fecha de fin es obligatoria.',
            'end_date.date_format'       => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'end_date.after_or_equal'    => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'department_id.exists'       => 'El departamento seleccionado no existe o pertenece a otra empresa.',
            'period_type.in'             => 'El tipo de periodo seleccionado es inválido.',
        ];
    }
}
