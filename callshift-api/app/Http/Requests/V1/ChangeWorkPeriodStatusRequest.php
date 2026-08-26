<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChangeWorkPeriodStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status'       => ['required', 'string', 'in:DRAFT,GENERATED,REVIEW,PUBLISHED,CLOSED'],
            'reason'       => ['nullable', 'string', 'max:255'],
            'lock_version' => ['required_if:status,PUBLISHED,CLOSED', 'nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado del periodo es obligatorio.',
            'status.in'       => 'Estado de periodo inválido.',
        ];
    }
}
