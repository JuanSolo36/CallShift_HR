<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertScheduleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'employee_id'   => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'date'          => ['required', 'date_format:Y-m-d'],
            'day_type'      => ['nullable', 'string', 'in:WORK,REST,OFF,HOLIDAY,PERMISSION,ABSENCE'],
            'shift_type_id' => [
                'nullable',
                'integer',
                Rule::exists('shift_types', 'id')->where('company_id', $companyId)->where('status', 'ACTIVE'),
            ],
            'lock_version'  => ['required', 'integer', 'min:1'],
            'notes'         => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'employee_id.required'   => 'El colaborador es obligatorio.',
            'employee_id.exists'     => 'El colaborador seleccionado no existe o pertenece a otra empresa.',
            'date.required'          => 'La fecha de asignación es obligatoria.',
            'date.date_format'       => 'Formato de fecha inválido (YYYY-MM-DD).',
            'shift_type_id.exists'   => 'El tipo de turno no existe, está inactivo o pertenece a otra empresa.',
            'lock_version.required'  => 'El número de versión de concurrencia (lock_version) es obligatorio.',
            'lock_version.min'       => 'El lock_version debe ser mayor o igual a 1.',
        ];
    }
}
