<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class DeleteScheduleAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lock_version' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'lock_version.required' => 'El lock_version es requerido para control de concurrencia.',
        ];
    }
}
