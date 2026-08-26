<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $shortType = class_basename((string)$this->auditable_type);

        $friendlyNames = [
            'User'                 => 'Usuario',
            'Company'              => 'Empresa',
            'Department'           => 'Departamento',
            'Position'             => 'Puesto / Cargo',
            'EmploymentType'       => 'Tipo de Empleo',
            'Employee'             => 'Empleado',
            'ShiftType'            => 'Tipo de Turno',
            'ShiftPattern'         => 'Patrón de Turnos',
            'ShiftTemplate'        => 'Plantilla de Turnos',
            'WorkPeriod'           => 'Periodo Laboral',
            'ScheduleVersion'      => 'Versión de Horario',
            'ScheduleAssignment'   => 'Asignación de Turno',
            'ScheduleModification' => 'Modificación de Horario',
            'ModificationEvidence' => 'Evidencia Documental',
            'BusinessRule'         => 'Regla de Negocio',
            'ScheduleConflict'     => 'Conflicto de Horario',
            'AuditLog'             => 'Bitácora de Auditoría',
        ];

        return [
            'id'                  => $this->id,
            'company_id'          => $this->company_id,
            'user_id'             => $this->user_id,
            'user'                => $this->whenLoaded('user', fn() => [
                'id'         => $this->user->id,
                'username'   => $this->user->username,
                'email'      => $this->user->email,
                'first_name' => $this->user->first_name ?? null,
                'last_name'  => $this->user->last_name ?? null,
            ], $this->user ? [
                'id'       => $this->user->id,
                'username' => $this->user->username,
                'email'    => $this->user->email,
            ] : null),
            'action'              => is_object($this->action) ? $this->action->value : $this->action,
            'action_label'        => is_object($this->action) && method_exists($this->action, 'label') ? $this->action->label() : (string)$this->action,
            'auditable_type'      => $this->auditable_type,
            'auditable_type_name' => $friendlyNames[$shortType] ?? $shortType,
            'auditable_id'        => $this->auditable_id,
            'description'         => $this->description,
            'old_values'          => $this->old_values,
            'new_values'          => $this->new_values,
            'ip_address'          => $this->ip_address,
            'user_agent'          => $this->user_agent,
            'created_at'          => $this->created_at?->toIso8601String(),
        ];
    }
}
