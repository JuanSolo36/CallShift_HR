<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Department;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class DepartmentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): Response
    {
        if ($actor->hasPermission('organization:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para listar departamentos.');
    }

    public function view(User $actor, Department $department): Response
    {
        if ($actor->company_id !== $department->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar departamentos de otra empresa.');
        }

        if ($actor->hasPermission('organization:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para ver este departamento.');
    }

    public function create(User $actor): Response
    {
        if ($actor->hasPermission('organization:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para crear departamentos.');
    }

    public function update(User $actor, Department $department): Response
    {
        if ($actor->company_id !== $department->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar departamentos de otra empresa.');
        }

        if ($actor->hasPermission('organization:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para actualizar este departamento.');
    }

    public function delete(User $actor, Department $department): Response
    {
        if ($actor->company_id !== $department->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar departamentos de otra empresa.');
        }

        if ($actor->hasPermission('organization:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para eliminar este departamento.');
    }
}
