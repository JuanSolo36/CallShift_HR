<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftPatternEntryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'shift_pattern_id'    => $this->shift_pattern_id,
            'day_number'          => (int) $this->day_number,
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
            'start_time_override' => $this->start_time_override ? substr($this->start_time_override, 0, 5) : null,
            'end_time_override'   => $this->end_time_override ? substr($this->end_time_override, 0, 5) : null,
            'notes'               => $this->notes,
        ];
    }
}
