<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTypeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $startTime = $this->start_time;
        if (is_string($startTime) && strlen($startTime) > 5) {
            $startTime = substr($startTime, 0, 5);
        }

        $endTime = $this->end_time;
        if (is_string($endTime) && strlen($endTime) > 5) {
            $endTime = substr($endTime, 0, 5);
        }

        return [
            'id'                     => $this->id,
            'company_id'             => $this->company_id,
            'name'                   => $this->name,
            'code'                   => $this->code,
            'color_hex'              => $this->color_hex ?? '#3B82F6',
            'start_time'             => $startTime,
            'end_time'               => $endTime,
            'break_duration_minutes' => (int) ($this->break_duration_minutes ?? 0),
            'total_work_hours'       => (float) $this->total_work_hours,
            'crosses_midnight'       => (bool) $this->crosses_midnight,
            'description'            => $this->description,
            'status'                 => $this->status,
            'assignments_count'      => $this->whenCounted('assignments', $this->assignments_count ?? $this->assignments()->count()),
            'created_at'             => $this->created_at?->toIso8601String(),
            'updated_at'             => $this->updated_at?->toIso8601String(),
        ];
    }
}
