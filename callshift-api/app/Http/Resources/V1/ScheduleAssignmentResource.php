<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class ScheduleAssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $dateStr = $this->date instanceof \DateTimeInterface ? $this->date->format('Y-m-d') : (is_string($this->date) ? substr($this->date, 0, 10) : $this->date);

        return [
            'id'                  => $this->id,
            'schedule_version_id' => $this->schedule_version_id,
            'employee_id'         => $this->employee_id,
            'date'                => $dateStr,
            'day_type'            => is_object($this->day_type) ? $this->day_type->value : $this->day_type,
            'shift_type_id'       => $this->shift_type_id,
            'shift_type'          => $this->whenLoaded('shiftType', function () {
                return $this->shiftType ? [
                    'id'               => $this->shiftType->id,
                    'name'             => $this->shiftType->name,
                    'code'             => $this->shiftType->code,
                    'color_hex'        => $this->shiftType->color_hex,
                    'start_time'       => substr($this->shiftType->start_time, 0, 5),
                    'end_time'         => substr($this->shiftType->end_time, 0, 5),
                    'total_work_hours' => (float) $this->shiftType->total_work_hours,
                    'crosses_midnight' => (bool) $this->shiftType->crosses_midnight,
                ] : null;
            }),
            'start_time'          => $this->start_time ? substr($this->start_time, 0, 5) : null,
            'end_time'            => $this->end_time ? substr($this->end_time, 0, 5) : null,
            'starts_at'           => $this->starts_at?->toIso8601String(),
            'ends_at'             => $this->ends_at?->toIso8601String(),
            'total_hours'         => (float) $this->total_hours,
            'is_custom'           => (bool) $this->is_custom,
            'notes'               => $this->notes,
            'created_at'          => $this->created_at?->toIso8601String(),
            'updated_at'          => $this->updated_at?->toIso8601String(),
        ];
    }
}
