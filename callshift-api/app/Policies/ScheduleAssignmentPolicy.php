<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use App\Enums\RoleCode;
use App\Enums\WorkPeriodStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ScheduleAssignmentPolicy
{
    use HandlesAuthorization;

    public function view(User $actor, ScheduleVersion $version): Response
    {
        $companyId = $version->workPeriod ? $version->workPeriod->company_id : null;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar horarios de otra empresa.');
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

        return Response::deny('No tiene permiso para consultar esta versión de horario.');
    }

    public function update(User $actor, ScheduleVersion $version): Response
    {
        $workPeriod = $version->workPeriod;
        $companyId = $workPeriod ? $workPeriod->company_id : null;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar horarios de otra empresa.');
        }

        // Validar inmutabilidad de la versión
        if ($version->isImmutable()) {
            return Response::deny("No se pueden modificar asignaciones en una versión en estado {$version->status->value}.");
        }

        // Validar estado del WorkPeriod
        if ($workPeriod && $workPeriod->status === WorkPeriodStatus::CLOSED) {
            return Response::deny('No se pueden modificar horarios de un periodo laboral CERRADO.');
        }

        if ($actor->hasPermission('schedules:update') || $actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para modificar celdas en este horario.');
    }

    public function delete(User $actor, ScheduleVersion $version): Response
    {
        return $this->update($actor, $version);
    }
}
