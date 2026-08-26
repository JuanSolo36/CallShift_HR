<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ScheduleVersion;
use App\Models\ScheduleModification;
use App\Models\ModificationEvidence;
use App\Enums\RoleCode;
use App\Enums\ScheduleVersionStatus;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ScheduleModificationPolicy
{
    use HandlesAuthorization;

    public function view(User $actor, ScheduleModification $modification): Response
    {
        $companyId = $modification->version?->workPeriod?->company_id;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar modificaciones de otra empresa.');
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

        return Response::deny('No tiene permiso para consultar esta modificación.');
    }

    public function create(User $actor, ScheduleVersion $version): Response
    {
        $companyId = $version->workPeriod?->company_id;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede crear modificaciones en otra empresa.');
        }

        if ($actor->hasRole(RoleCode::VIEWER->value)) {
            return Response::deny('Los usuarios con rol de solo lectura (VIEWER) no pueden registrar modificaciones.');
        }

        if ($actor->hasPermission('schedules:update') || $actor->hasPermission('schedules:create') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
            RoleCode::SUPERVISOR->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para registrar modificaciones de horario.');
    }

    public function attachEvidence(User $actor, ScheduleModification $modification): Response
    {
        return $this->create($actor, $modification->version);
    }

    public function downloadEvidence(User $actor, ModificationEvidence $evidence): Response
    {
        $companyId = $evidence->modification?->version?->workPeriod?->company_id;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede descargar evidencias de otra empresa.');
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

        return Response::deny('No tiene permiso para descargar evidencias de esta modificación.');
    }

    public function deleteEvidence(User $actor, ModificationEvidence $evidence): Response
    {
        $version = $evidence->modification?->version;
        $companyId = $version?->workPeriod?->company_id;

        if ($companyId !== $actor->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar evidencias de otra empresa.');
        }

        if ($version && $version->status !== ScheduleVersionStatus::DRAFT) {
            return Response::deny("Violación de inmutabilidad: No se pueden eliminar evidencias asociadas a versiones en estado {$version->status->value}.");
        }

        if ($actor->hasRole(RoleCode::VIEWER->value)) {
            return Response::deny('Los usuarios con rol VIEWER no pueden eliminar evidencias.');
        }

        if ($actor->hasPermission('schedules:update') || $actor->hasRole([
            RoleCode::SUPER_ADMIN->value,
            RoleCode::HR_ADMIN->value,
            RoleCode::MANAGER->value,
        ])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para eliminar esta evidencia.');
    }
}
