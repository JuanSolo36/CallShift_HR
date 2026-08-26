<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;

class RestoreScheduleVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'target_version_id' => 'required|integer|exists:schedule_versions,id',
            'reason'            => 'required|string|min:5|max:1000',
        ];
    }
}
