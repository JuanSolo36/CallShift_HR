<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PositionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'company_id'      => $this->company_id,
            'department_id'   => $this->department_id,
            'name'            => $this->name,
            'code'            => $this->code,
            'description'     => $this->description,
            'status'          => $this->status,
            'department'      => $this->whenLoaded('department', function () {
                if (!$this->department) return null;
                return [
                    'id'               => $this->department->id,
                    'name'             => $this->department->name,
                    'code'             => $this->department->code,
                    'cost_center_code' => $this->department->cost_center_code,
                ];
            }),
            'employees_count' => $this->whenCounted('employees', $this->employees_count ?? $this->employees()->count()),
            'created_at'      => $this->created_at?->toIso8601String(),
            'updated_at'      => $this->updated_at?->toIso8601String(),
        ];
    }
}
