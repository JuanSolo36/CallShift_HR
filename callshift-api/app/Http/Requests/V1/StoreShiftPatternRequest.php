<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftPatternRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'                => ['required', 'string', 'max:100'],
            'code'                => [
                'required',
                'string',
                'max:30',
                Rule::unique('shift_patterns', 'code')->where('company_id', $companyId),
            ],
            'department_id'       => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $companyId),
            ],
            'position_id'         => [
                'nullable',
                'integer',
                Rule::exists('positions', 'id')->where('company_id', $companyId),
            ],
            'cycle_length_days'   => ['required', 'integer', 'min:1', 'max:365'],
            'description'         => ['nullable', 'string', 'max:500'],
            'status'              => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
            'entries'             => ['required', 'array', 'min:1'],
            'entries.*.day_number'       => ['required', 'integer', 'min:1'],
            'entries.*.day_type'         => ['required', 'string', 'in:WORK,REST,OFF,HOLIDAY,PERMISSION,ABSENCE'],
            'entries.*.shift_type_id'    => [
                'nullable',
                'integer',
                Rule::exists('shift_types', 'id')->where('company_id', $companyId)->where('status', 'ACTIVE'),
            ],
            'entries.*.start_time_override' => ['nullable', 'date_format:H:i:s,H:i'],
            'entries.*.end_time_override'   => ['nullable', 'date_format:H:i:s,H:i'],
            'entries.*.notes'               => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'              => 'El nombre del patrón es obligatorio.',
            'code.required'              => 'El código del patrón es obligatorio.',
            'code.unique'                => 'Ya existe un patrón de turno con este código en la empresa.',
            'cycle_length_days.required' => 'La longitud del ciclo en días es obligatoria.',
            'cycle_length_days.min'      => 'La longitud del ciclo debe ser de al menos 1 día.',
            'entries.required'           => 'Debe definir las entradas de la secuencia del ciclo.',
            'entries.*.day_number.required' => 'El número de día del ciclo es obligatorio.',
            'entries.*.day_type.required'   => 'El tipo de día es obligatorio para cada entrada.',
            'entries.*.shift_type_id.exists' => 'El tipo de turno seleccionado no existe, está inactivo o pertenece a otra empresa.',
        ];
    }
}
