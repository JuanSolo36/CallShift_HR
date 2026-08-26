<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_version_id' => 'nullable|integer|exists:schedule_versions,id',
            'change_summary'    => 'nullable|string|max:1000',
        ];
    }
}
