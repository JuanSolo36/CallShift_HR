<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'          => ['sometimes', 'required', 'string', 'max:100'],
            'period_type'   => ['sometimes', 'required', 'string', 'in:WEEKLY,BIWEEKLY,MONTHLY,CUSTOM'],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'start_date'    => ['sometimes', 'required', 'date_format:Y-m-d'],
            'end_date'      => ['sometimes', 'required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'status'        => ['sometimes', 'required', 'string', 'in:DRAFT,GENERATED,REVIEW,PUBLISHED,CLOSED'],
            'lock_version'  => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
            'department_id.exists'    => 'El departamento seleccionado no existe o pertenece a otra empresa.',
        ];
    }
}
