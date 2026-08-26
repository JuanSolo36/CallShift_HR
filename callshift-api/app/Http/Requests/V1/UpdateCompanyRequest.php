<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $companyId = $this->user()?->company_id;

        return [
            'name'            => ['sometimes', 'required', 'string', 'max:150'],
            'legal_name'      => ['sometimes', 'required', 'string', 'max:200'],
            'tax_id'          => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('companies', 'tax_id')->ignore($companyId),
            ],
            'slug'            => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash',
                Rule::unique('companies', 'slug')->ignore($companyId),
            ],
            'email'           => ['sometimes', 'required', 'string', 'email:rfc,dns', 'max:120'],
            'phone'           => ['nullable', 'string', 'max:30'],
            'address'         => ['nullable', 'string', 'max:255'],
            'city'            => ['nullable', 'string', 'max:100'],
            'country'         => ['sometimes', 'required', 'string', 'size:3'],
            'timezone'        => ['sometimes', 'required', 'string', 'timezone:all'],
            'currency'        => ['sometimes', 'required', 'string', 'max:10'],
            'date_format'     => ['sometimes', 'required', 'string', 'max:20'],
            'logo'            => ['nullable', 'string', 'max:255'],
            'primary_color'   => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'secondary_color' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'tax_id.unique'           => 'Este número de identificación tributaria (NIT) ya está registrado en otra empresa.',
            'slug.unique'             => 'Este identificador de enlace (slug) ya se encuentra en uso por otra empresa.',
            'slug.alpha_dash'         => 'El slug solo puede contener caracteres alfanuméricos, guiones y guiones bajos.',
            'primary_color.regex'     => 'El color primario debe ser un código hexadecimal válido (ej. #0284c7).',
            'secondary_color.regex'   => 'El color secundario debe ser un código hexadecimal válido (ej. #0f172a).',
        ];
    }
}
