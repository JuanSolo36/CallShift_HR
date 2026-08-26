<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class ChangeUserStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE,SUSPENDED'],
            'reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'El nuevo estado es obligatorio.',
            'status.in'       => 'El estado debe ser ACTIVE, INACTIVE o SUSPENDED.',
        ];
    }
}
