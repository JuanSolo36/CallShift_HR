<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ShiftPattern;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\Response;

class ShiftPatternPolicy
{
    /**
     * Determina si el usuario puede ver la lista de patrones.
     */
    public function viewAny(User $user): Response
    {
        if ($user->hasPermission('shifts:view') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para consultar patrones de turno.');
    }

    /**
     * Determina si el usuario puede ver un patrón específico.
     */
    public function view(User $user, ShiftPattern $pattern): Response
    {
        if ($user->company_id !== $pattern->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El patrón pertenece a otra empresa.');
        }

        if ($user->hasPermission('shifts:view') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para ver este patrón de turno.');
    }

    /**
     * Determina si el usuario puede crear patrones.
     */
    public function create(User $user): Response
    {
        if ($user->hasPermission('shifts:manage') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para crear patrones de turno.');
    }

    /**
     * Determina si el usuario puede actualizar un patrón.
     */
    public function update(User $user, ShiftPattern $pattern): Response
    {
        if ($user->company_id !== $pattern->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar patrones de otra empresa.');
        }

        if ($user->hasPermission('shifts:manage') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para modificar este patrón de turno.');
    }

    /**
     * Determina si el usuario puede eliminar un patrón.
     */
    public function delete(User $user, ShiftPattern $pattern): Response
    {
        if ($user->company_id !== $pattern->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar patrones de otra empresa.');
        }

        if ($user->hasPermission('shifts:manage') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para eliminar este patrón de turno.');
    }

    /**
     * Determina si el usuario puede aplicar un patrón sobre mallas de horarios.
     */
    public function apply(User $user, ShiftPattern $pattern): Response
    {
        if ($user->company_id !== $pattern->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El patrón pertenece a otra empresa.');
        }

        if ($user->hasPermission('schedules:update') || $user->hasPermission('schedules:create') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para aplicar patrones de turno a horarios.');
    }
}
