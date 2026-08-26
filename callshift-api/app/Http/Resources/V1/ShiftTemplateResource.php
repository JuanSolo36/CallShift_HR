<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftTemplateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'company_id'       => $this->company_id,
            'department_id'    => $this->department_id,
            'department'       => $this->whenLoaded('department', function () {
                return $this->department ? [
                    'id'   => $this->department->id,
                    'name' => $this->department->name,
                    'code' => $this->department->code,
                ] : null;
            }),
            'position_id'      => $this->position_id,
            'position'         => $this->whenLoaded('position', function () {
                return $this->position ? [
                    'id'   => $this->position->id,
                    'name' => $this->position->name,
                    'code' => $this->position->code,
                ] : null;
            }),
            'shift_pattern_id' => $this->shift_pattern_id,
            'pattern'          => $this->whenLoaded('pattern', function () {
                return $this->pattern ? new ShiftPatternResource($this->pattern) : null;
            }),
            'name'             => $this->name,
            'code'             => $this->code,
            'description'      => $this->description,
            'status'           => $this->status,
            'metadata'         => $this->metadata,
            'created_by'       => $this->created_by,
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
