<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\ModificationType;
use App\Enums\DayType;
use Illuminate\Validation\Rules\Enum;

class StoreScheduleModificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'schedule_assignment_id' => ['required', 'integer'],
            'employee_id'            => ['required', 'integer'],
            'modification_type'      => ['required', 'string', new Enum(ModificationType::class)],
            'reason'                 => ['required', 'string', 'min:5'],
            'shift_type_id'          => ['nullable', 'integer'],
            'day_type'               => ['nullable', 'string', new Enum(DayType::class)],
            'start_time'             => ['nullable', 'string'],
            'end_time'               => ['nullable', 'string'],
            'starts_at'              => ['nullable', 'date'],
            'ends_at'                => ['nullable', 'date'],
            'break_start'            => ['nullable', 'string'],
            'break_end'              => ['nullable', 'string'],
            'total_hours'            => ['nullable', 'numeric', 'min:0', 'max:24'],
            'is_custom'              => ['nullable', 'boolean'],
            'notes'                  => ['nullable', 'string', 'max:500'],
            'evidences'              => ['nullable', 'array'],
            'evidences.*'            => ['file', 'max:10240', 'mimes:pdf,png,jpg,jpeg'],
        ];
    }

    public function messages(): array
    {
        return [
            'schedule_assignment_id.required' => 'La asignación de horario es requerida.',
            'employee_id.required'            => 'El empleado afectado es requerido.',
            'modification_type.required'      => 'El tipo de modificación es requerido.',
            'reason.required'                 => 'El motivo de la modificación es obligatorio.',
            'reason.min'                      => 'El motivo de la modificación debe tener al menos 5 caracteres.',
            'evidences.*.max'                 => 'Cada archivo de evidencia no debe superar los 10 MB.',
            'evidences.*.mimes'               => 'Los formatos de evidencia permitidos son PDF, PNG, JPG y JPEG.',
        ];
    }
}
