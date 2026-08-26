<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShiftTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'                   => ['required', 'string', 'max:80'],
            'code'                   => [
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('shift_types', 'code')->where('company_id', $companyId),
            ],
            'color_hex'              => ['required', 'string', 'regex:/^#([a-fA-F0-9]{6})$/i'],
            'start_time'             => ['required', 'date_format:H:i'],
            'end_time'               => ['required', 'date_format:H:i'],
            'break_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:360'],
            'total_work_hours'       => ['nullable', 'numeric', 'min:0.5', 'max:24.0'],
            'crosses_midnight'       => ['nullable', 'boolean'],
            'description'            => ['nullable', 'string', 'max:500'],
            'status'                 => ['nullable', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'                   => 'El nombre del tipo de turno es obligatorio.',
            'code.required'                   => 'El código del turno es obligatorio.',
            'code.unique'                     => 'Este código de turno ya existe en su empresa.',
            'color_hex.required'              => 'El color del turno es obligatorio.',
            'color_hex.regex'                 => 'El color debe ser un código hexadecimal válido (ej. #3B82F6).',
            'start_time.required'             => 'La hora de inicio es obligatoria.',
            'start_time.date_format'          => 'Formato de hora de inicio inválido (utilice HH:mm en formato 24 horas).',
            'end_time.required'               => 'La hora de fin es obligatoria.',
            'end_time.date_format'            => 'Formato de hora de fin inválido (utilice HH:mm en formato 24 horas).',
            'break_duration_minutes.min'      => 'El tiempo de descanso no puede ser negativo.',
            'break_duration_minutes.max'      => 'El tiempo de descanso no puede superar los 360 minutos (6 horas).',
            'total_work_hours.min'            => 'La duración efectiva debe ser de al menos 0.5 horas.',
            'total_work_hours.max'            => 'La duración efectiva no puede exceder las 24 horas.',
        ];
    }
}
