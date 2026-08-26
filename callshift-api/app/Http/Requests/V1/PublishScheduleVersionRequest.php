<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class PublishScheduleVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'lock_version'   => 'required|integer',
            'change_summary' => 'nullable|string|max:1000',
        ];
    }
}
