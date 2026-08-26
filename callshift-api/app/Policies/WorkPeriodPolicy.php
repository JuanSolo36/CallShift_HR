<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkPeriod;
use App\Enums\RoleCode;
use App\Enums\WorkPeriodStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class WorkPeriodPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): Response
    {
        if ($actor->hasPermission('schedules:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::EMPLOYEE->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para consultar periodos laborales.');
    }

    public function view(User $actor, WorkPeriod $period): Response
    {
        if ($actor->company_id !== $period->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar periodos laborales de otra empresa.');
        }

        if ($actor->hasPermission('schedules:view') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
            RoleCode::EMPLOYEE->value,
            RoleCode::VIEWER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para ver este periodo laboral.');
    }

    public function create(User $actor): Response
    {
        if ($actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para crear periodos laborales.');
    }

    public function update(User $actor, WorkPeriod $period): Response
    {
        if ($actor->company_id !== $period->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar periodos laborales de otra empresa.');
        }

        if ($period->status === WorkPeriodStatus::CLOSED) {
            return Response::deny('No se puede modificar un periodo laboral que ya se encuentra CERRADO.');
        }

        if ($actor->hasPermission('schedules:update') || $actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para actualizar este periodo laboral.');
    }

    public function changeStatus(User $actor, WorkPeriod $period): Response
    {
        if ($actor->company_id !== $period->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede cambiar el estado de periodos laborales de otra empresa.');
        }

        if ($actor->hasPermission('schedules:publish') || $actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para modificar el estado de este periodo laboral.');
    }

    public function delete(User $actor, WorkPeriod $period): Response
    {
        if ($actor->company_id !== $period->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar periodos laborales de otra empresa.');
        }

        if ($period->status === WorkPeriodStatus::CLOSED || $period->status === WorkPeriodStatus::PUBLISHED) {
            return Response::deny('No se puede eliminar un periodo laboral que ya ha sido PUBLICADO o CERRADO.');
        }

        if ($actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para eliminar este periodo laboral.');
    }
}
