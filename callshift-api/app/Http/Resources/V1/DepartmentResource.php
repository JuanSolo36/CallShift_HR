<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DepartmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'company_id'       => $this->company_id,
            'name'             => $this->name,
            'code'             => $this->code,
            'cost_center_code' => $this->cost_center_code,
            'description'      => $this->description,
            'status'           => $this->status,
            'manager_id'       => $this->manager_id,
            'manager'          => $this->whenLoaded('manager', function () {
                if (!$this->manager) return null;
                return [
                    'id'            => $this->manager->id,
                    'employee_code' => $this->manager->employee_code,
                    'full_name'     => $this->manager->first_name . ' ' . $this->manager->last_name,
                    'email'         => $this->manager->email,
                ];
            }),
            'positions_count'  => $this->whenCounted('positions', $this->positions_count ?? $this->positions()->count()),
            'employees_count'  => $this->whenCounted('employees', $this->employees_count ?? $this->employees()->count()),
            'created_at'       => $this->created_at?->toIso8601String(),
            'updated_at'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
