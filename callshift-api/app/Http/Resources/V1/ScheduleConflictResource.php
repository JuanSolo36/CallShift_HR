<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleConflictResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                        => $this->id,
            'schedule_version_id'       => $this->schedule_version_id,
            'employee_id'               => $this->employee_id,
            'employee'                  => $this->whenLoaded('employee', fn() => [
                'id'         => $this->employee->id,
                'first_name' => $this->employee->first_name,
                'last_name'  => $this->employee->last_name,
                'code'       => $this->employee->code,
                'department' => $this->employee->department ? [
                    'id'   => $this->employee->department->id,
                    'name' => $this->employee->department->name,
                ] : null,
            ]),
            'conflict_key'              => $this->conflict_key,
            'date'                      => $this->date instanceof \DateTimeInterface ? $this->date->format('Y-m-d') : $this->date,
            'start_datetime'            => $this->start_datetime?->toISOString(),
            'end_datetime'              => $this->end_datetime?->toISOString(),
            'severity'                  => is_object($this->severity) ? $this->severity->value : $this->severity,
            'rule_violated'             => $this->rule_violated,
            'description'               => $this->description,
            'suggested_resolution'      => $this->suggested_resolution,
            'primary_assignment_id'     => $this->primary_assignment_id,
            'conflicting_assignment_id' => $this->conflicting_assignment_id,
            'status'                    => is_object($this->status) ? $this->status->value : $this->status,
            'is_resolved'               => (bool) $this->is_resolved,
            'resolved_by'               => $this->resolved_by,
            'resolver'                  => $this->whenLoaded('resolver', fn() => [
                'id'       => $this->resolver->id,
                'name'     => $this->resolver->name,
                'username' => $this->resolver->username,
            ]),
            'resolved_at'               => $this->resolved_at?->toISOString(),
            'resolution_reason'         => $this->resolution_reason,
            'created_at'                => $this->created_at?->toISOString(),
            'updated_at'                => $this->updated_at?->toISOString(),
        ];
    }
}
