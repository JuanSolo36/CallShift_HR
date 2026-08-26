<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BusinessRuleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                            => $this->id,
            'company_id'                    => $this->company_id,
            'department_id'                 => $this->department_id,
            'department_scope_id'           => $this->department_scope_id,
            'is_global'                     => $this->department_scope_id === 0,
            'department'                    => $this->whenLoaded('department', fn() => [
                'id'   => $this->department->id,
                'name' => $this->department->name,
                'code' => $this->department->code,
            ]),
            'max_daily_hours'               => $this->max_daily_hours !== null ? (float) $this->max_daily_hours : null,
            'min_daily_hours'               => $this->min_daily_hours !== null ? (float) $this->min_daily_hours : null,
            'max_weekly_hours'              => $this->max_weekly_hours !== null ? (float) $this->max_weekly_hours : null,
            'min_weekly_hours'              => $this->min_weekly_hours !== null ? (float) $this->min_weekly_hours : null,
            'min_rest_hours_between_shifts' => $this->min_rest_hours_between_shifts !== null ? (float) $this->min_rest_hours_between_shifts : null,
            'max_consecutive_work_days'     => $this->max_consecutive_work_days !== null ? (int) $this->max_consecutive_work_days : null,
            'allow_night_shifts'            => $this->allow_night_shifts,
            'weekend_rotation_policy'       => is_object($this->weekend_rotation_policy) ? $this->weekend_rotation_policy->value : $this->weekend_rotation_policy,
            'created_at'                    => $this->created_at?->toISOString(),
            'updated_at'                    => $this->updated_at?->toISOString(),
        ];
    }
}
