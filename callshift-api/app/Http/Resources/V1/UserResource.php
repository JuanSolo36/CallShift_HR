<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Cargar relaciones si no están ya cargadas
        $this->resource->loadMissing(['role.permissions', 'employee.department', 'employee.position', 'company']);

        // Extraer lista plana de permisos según rol
        $permissions = [];
        if ($this->role) {
            if ($this->role->code === 'SUPER_ADMIN') {
                $permissions = ['*']; // Acceso total
            } else {
                $permissions = $this->role->permissions->pluck('code')->values()->all();
            }
        }

        return [
            'id'            => $this->id,
            'company_id'    => $this->company_id,
            'username'      => $this->username,
            'email'         => $this->email,
            'status'        => $this->status,
            'last_login_at' => $this->last_login_at?->toIso8601String(),
            'role'          => $this->role ? [
                'id'          => $this->role->id,
                'code'        => $this->role->code,
                'name'        => $this->role->name,
                'description' => $this->role->description,
            ] : null,
            'permissions'   => $permissions,
            'employee'      => $this->employee ? [
                'id'            => $this->employee->id,
                'employee_code' => $this->employee->employee_code,
                'full_name'     => $this->employee->full_name,
                'first_name'    => $this->employee->first_name,
                'last_name'     => $this->employee->last_name,
                'email'         => $this->employee->email,
                'department'    => $this->employee->department ? [
                    'id'   => $this->employee->department->id,
                    'name' => $this->employee->department->name,
                    'code' => $this->employee->department->code,
                ] : null,
                'position'      => $this->employee->position ? [
                    'id'   => $this->employee->position->id,
                    'name' => $this->employee->position->name,
                    'code' => $this->employee->position->code,
                ] : null,
            ] : null,
            'company'       => $this->company ? [
                'id'        => $this->company->id,
                'name'      => $this->company->name,
                'timezone'  => $this->company->timezone,
                'country'   => $this->company->country,
            ] : null,
        ];
    }
}
