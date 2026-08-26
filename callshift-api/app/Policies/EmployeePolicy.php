<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Employee;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): Response
    {
        if ($actor->hasPermission('employees:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para consultar el listado de empleados.');
    }

    public function view(User $actor, Employee $employee): Response
    {
        // 1. Aislamiento estricto multi-tenant
        if ($actor->company_id !== $employee->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar expedientes de empleados de otra empresa.');
        }

        // 2. Permiso de visualización
        if ($actor->hasPermission('employees:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        // 3. Auto-consulta permitida (un usuario vinculado a su propio empleado)
        if ($actor->employee_id === $employee->id) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para ver el expediente de este empleado.');
    }

    public function create(User $actor): Response
    {
        if ($actor->hasPermission('employees:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para registrar nuevos empleados.');
    }

    public function update(User $actor, Employee $employee): Response
    {
        // 1. Aislamiento estricto multi-tenant
        if ($actor->company_id !== $employee->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar empleados de otra empresa.');
        }

        // 2. Permiso de actualización
        if ($actor->hasPermission('employees:update') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para actualizar la información de este empleado.');
    }

    public function delete(User $actor, Employee $employee): Response
    {
        // 1. Aislamiento estricto multi-tenant
        if ($actor->company_id !== $employee->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede dar de baja empleados de otra empresa.');
        }

        // 2. Permiso de eliminación/desactivación
        if ($actor->hasPermission('employees:delete') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para desactivar o dar de baja este empleado.');
    }
}
