<?php

namespace App\Policies;

use App\Models\User;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class AuditLogPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el usuario puede listar los registros de auditoría de su empresa.
     */
    public function viewAny(User $actor): Response
    {
        if ($actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value]) 
            || $actor->hasPermission('audit:view')
            || $actor->hasPermission('schedules:view')) {
            return Response::allow();
        }

        return Response::deny('No tiene autorización para consultar los registros de auditoría.');
    }

    /**
     * Determina si el usuario puede consultar el detalle de un registro específico.
     */
    public function view(User $actor, AuditLog $log): Response
    {
        if ($log->company_id !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El registro de auditoría pertenece a otra empresa.');
        }

        return $this->viewAny($actor);
    }

    /**
     * Determina si el usuario puede exportar los registros de auditoría.
     */
    public function export(User $actor): Response
    {
        if ($actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value]) 
            || $actor->hasPermission('audit:export')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para exportar registros de auditoría.');
    }

    /**
     * Bloqueo incondicional de mutaciones directas sobre la bitácora.
     */
    public function create(User $actor): Response
    {
        return Response::deny('La bitácora de auditoría es de solo anexado (Append-Only) y no permite creación manual.');
    }

    public function update(User $actor, AuditLog $log): Response
    {
        return Response::deny('La bitácora de auditoría es inmutable y no permite modificaciones.');
    }

    public function delete(User $actor, AuditLog $log): Response
    {
        return Response::deny('La bitácora de auditoría es inmutable y no permite eliminaciones.');
    }
}
