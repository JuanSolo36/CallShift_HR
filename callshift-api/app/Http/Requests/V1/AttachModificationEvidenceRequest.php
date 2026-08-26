<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class AttachModificationEvidenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,png,jpg,jpeg'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'El archivo de evidencia es obligatorio.',
            'file.max'      => 'El archivo de evidencia no debe superar los 10 MB.',
            'file.mimes'    => 'Los formatos permitidos para la evidencia son PDF, PNG, JPG y JPEG.',
        ];
    }
}
