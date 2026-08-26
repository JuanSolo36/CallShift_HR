<?php

namespace App\Policies;

use App\Models\User;
use App\Models\EmploymentType;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EmploymentTypePolicy
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

        return Response::deny('No tiene permiso para listar tipos de contrato.');
    }

    public function view(User $actor, EmploymentType $employmentType): Response
    {
        if ($actor->company_id !== $employmentType->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar tipos de contrato de otra empresa.');
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

        return Response::deny('No tiene permiso para ver este tipo de contrato.');
    }

    public function create(User $actor): Response
    {
        if ($actor->hasPermission('organization:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para crear tipos de contrato.');
    }

    public function update(User $actor, EmploymentType $employmentType): Response
    {
        if ($actor->company_id !== $employmentType->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar tipos de contrato de otra empresa.');
        }

        if ($actor->hasPermission('organization:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para actualizar este tipo de contrato.');
    }

    public function delete(User $actor, EmploymentType $employmentType): Response
    {
        if ($actor->company_id !== $employmentType->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar tipos de contrato de otra empresa.');
        }

        if ($actor->hasPermission('organization:manage') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para eliminar este tipo de contrato.');
    }
}
