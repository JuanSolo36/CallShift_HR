<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBusinessRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'max_daily_hours'               => ['nullable', 'numeric', 'min:1', 'max:24'],
            'min_daily_hours'               => ['nullable', 'numeric', 'min:0', 'max:24'],
            'max_weekly_hours'              => ['nullable', 'numeric', 'min:1', 'max:168'],
            'min_weekly_hours'              => ['nullable', 'numeric', 'min:0', 'max:168'],
            'min_rest_hours_between_shifts' => ['nullable', 'numeric', 'min:0', 'max:48'],
            'max_consecutive_work_days'     => ['nullable', 'integer', 'min:1', 'max:30'],
            'allow_night_shifts'            => ['nullable', 'boolean'],
            'weekend_rotation_policy'       => ['nullable', 'string', Rule::in(['STRICT_ROTATION', 'FAIR_SHARE', 'NONE'])],
        ];
    }
}
