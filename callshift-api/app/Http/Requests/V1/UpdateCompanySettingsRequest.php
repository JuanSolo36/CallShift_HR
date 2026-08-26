<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timezone'        => ['sometimes', 'required', 'string', 'timezone:all'],
            'currency'        => ['sometimes', 'required', 'string', 'max:10'],
            'date_format'     => ['sometimes', 'required', 'string', 'max:20'],
            'primary_color'   => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'settings'        => ['nullable', 'array'],
            'settings.*'      => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'primary_color.regex'   => 'El color primario debe ser un código hexadecimal válido (ej. #0284c7).',
            'secondary_color.regex' => 'El color secundario debe ser un código hexadecimal válido (ej. #0f172a).',
        ];
    }
}
