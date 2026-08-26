<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'company_id'         => $this->company_id,
            'employee_code'      => $this->employee_code,
            'document_type'      => $this->document_type,
            'document_number'    => $this->document_number,
            'first_name'         => $this->first_name,
            'middle_name'        => $this->middle_name,
            'last_name'          => $this->last_name,
            'second_last_name'   => $this->second_last_name,
            'full_name'          => $this->full_name,
            'email'              => $this->email,
            'personal_email'     => $this->personal_email,
            'phone'              => $this->phone,
            'birth_date'         => $this->birth_date?->format('Y-m-d'),
            'hire_date'          => $this->hire_date?->format('Y-m-d'),
            'termination_date'   => $this->termination_date?->format('Y-m-d'),
            'department_id'      => $this->department_id,
            'position_id'        => $this->position_id,
            'employment_type_id' => $this->employment_type_id,
            'supervisor_id'      => $this->supervisor_id,
            'status'             => $this->status instanceof \BackedEnum ? $this->status->value : $this->status,
            'notes'              => $this->notes,

            // Relaciones cargadas de forma optimizada
            'department'         => $this->whenLoaded('department', function () {
                if (!$this->department) return null;
                return [
                    'id'               => $this->department->id,
                    'name'             => $this->department->name,
                    'code'             => $this->department->code,
                    'cost_center_code' => $this->department->cost_center_code,
                ];
            }),
            'position'           => $this->whenLoaded('position', function () {
                if (!$this->position) return null;
                return [
                    'id'   => $this->position->id,
                    'name' => $this->position->name,
                    'code' => $this->position->code,
                ];
            }),
            'employment_type'    => $this->whenLoaded('employmentType', function () {
                if (!$this->employmentType) return null;
                return [
                    'id'                   => $this->employmentType->id,
                    'name'                 => $this->employmentType->name,
                    'code'                 => $this->employmentType->code,
                    'default_weekly_hours' => (float) $this->employmentType->default_weekly_hours,
                ];
            }),
            'supervisor'         => $this->whenLoaded('supervisor', function () {
                if (!$this->supervisor) return null;
                return [
                    'id'            => $this->supervisor->id,
                    'employee_code' => $this->supervisor->employee_code,
                    'full_name'     => $this->supervisor->full_name,
                    'email'         => $this->supervisor->email,
                ];
            }),
            'user'               => $this->whenLoaded('user', function () {
                if (!$this->user) return null;
                return [
                    'id'       => $this->user->id,
                    'username' => $this->user->username,
                    'email'    => $this->user->email,
                    'status'   => $this->user->status,
                    'role'     => $this->user->role ? [
                        'id'   => $this->user->role->id,
                        'code' => $this->user->role->code,
                        'name' => $this->user->role->name,
                    ] : null,
                ];
            }),

            'created_at'         => $this->created_at?->toIso8601String(),
            'updated_at'         => $this->updated_at?->toIso8601String(),
        ];
    }
}
