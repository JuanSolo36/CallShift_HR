<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\ShiftType;

class UpdateShiftTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $target = $this->route('shift_type') ?? $this->route('shift-type');
        $shiftTypeId = $target instanceof ShiftType ? $target->id : ($this->route('id') ?? $target);
        $companyId = $this->user()?->company_id;

        return [
            'name'                   => ['sometimes', 'required', 'string', 'max:80'],
            'code'                   => [
                'sometimes',
                'required',
                'string',
                'max:30',
                'alpha_dash',
                Rule::unique('shift_types', 'code')
                    ->where('company_id', $companyId)
                    ->ignore($shiftTypeId),
            ],
            'color_hex'              => ['sometimes', 'required', 'string', 'regex:/^#([a-fA-F0-9]{6})$/i'],
            'start_time'             => ['sometimes', 'required', 'date_format:H:i'],
            'end_time'               => ['sometimes', 'required', 'date_format:H:i'],
            'break_duration_minutes' => ['nullable', 'integer', 'min:0', 'max:360'],
            'total_work_hours'       => ['nullable', 'numeric', 'min:0.5', 'max:24.0'],
            'crosses_midnight'       => ['nullable', 'boolean'],
            'description'            => ['nullable', 'string', 'max:500'],
            'status'                 => ['sometimes', 'required', 'string', 'in:ACTIVE,INACTIVE'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique'        => 'Este código de turno ya está en uso en su empresa.',
            'color_hex.regex'    => 'El color debe ser un código hexadecimal válido (ej. #3B82F6).',
            'start_time.date_format' => 'Formato de hora de inicio inválido (HH:mm).',
            'end_time.date_format'   => 'Formato de hora de fin inválido (HH:mm).',
        ];
    }
}
