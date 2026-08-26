<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Enums\RoleCode;
use App\Enums\WorkPeriodStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ScheduleVersionPolicy
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

    public function create(User $actor, WorkPeriod $period): Response
    {
        if ($period->company_id !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede crear versiones en otra empresa.');
        }

        if ($period->status === WorkPeriodStatus::CLOSED) {
            return Response::deny('No se pueden crear versiones en un periodo laboral CERRADO.');
        }

        if ($actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para crear versiones de horario.');
    }

    public function update(User $actor, ScheduleVersion $version): Response
    {
        $workPeriod = $version->workPeriod;
        $companyId = $workPeriod ? $workPeriod->company_id : null;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar horarios de otra empresa.');
        }

        if ($version->isImmutable()) {
            return Response::deny("No se pueden modificar asignaciones en una versión en estado {$version->status->value}.");
        }

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

    public function review(User $actor, ScheduleVersion $version): Response
    {
        $companyId = $version->workPeriod ? $version->workPeriod->company_id : null;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede operar horarios de otra empresa.');
        }

        if ($actor->hasPermission('schedules:update') || $actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para enviar o retornar versiones a revisión.');
    }

    public function publish(User $actor, ScheduleVersion $version): Response
    {
        $companyId = $version->workPeriod ? $version->workPeriod->company_id : null;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede publicar horarios de otra empresa.');
        }

        if ($actor->hasPermission('schedules:publish') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para publicar horarios oficiales.');
    }

    public function restore(User $actor, WorkPeriod $period): Response
    {
        return $this->create($actor, $period);
    }

    public function delete(User $actor, ScheduleVersion $version): Response
    {
        return $this->update($actor, $version);
    }
}
