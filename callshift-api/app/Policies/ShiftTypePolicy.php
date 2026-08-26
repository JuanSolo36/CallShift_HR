<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ShiftType;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ShiftTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): Response
    {
        if ($actor->hasPermission('shifts:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para consultar los tipos de turno.');
    }

    public function view(User $actor, ShiftType $shiftType): Response
    {
        if ($actor->company_id !== $shiftType->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar tipos de turno de otra empresa.');
        }

        if ($actor->hasPermission('shifts:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para ver este tipo de turno.');
    }

    public function create(User $actor): Response
    {
        if ($actor->hasPermission('shifts:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para crear tipos de turno.');
    }

    public function update(User $actor, ShiftType $shiftType): Response
    {
        if ($actor->company_id !== $shiftType->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar tipos de turno de otra empresa.');
        }

        if ($actor->hasPermission('shifts:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para actualizar este tipo de turno.');
    }

    public function delete(User $actor, ShiftType $shiftType): Response
    {
        if ($actor->company_id !== $shiftType->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar tipos de turno de otra empresa.');
        }

        if ($actor->hasPermission('shifts:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para eliminar este tipo de turno.');
    }
}
