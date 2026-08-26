<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\ScheduleConflict;
use App\Models\ScheduleVersion;
use App\Models\WorkPeriod;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ScheduleConflictPolicy
{
    public function viewAny(User $user): Response
    {
        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])
            || $user->hasPermission('schedules:view')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para consultar conflictos de horario.');
    }

    public function view(User $user, ScheduleConflict $conflict): Response
    {
        $companyId = $conflict->version?->workPeriod?->company_id;
        if ($companyId && $user->company_id !== $companyId && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El conflicto pertenece a otra empresa.');
        }

        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])
            || $user->hasPermission('schedules:view')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para ver este conflicto de horario.');
    }

    public function validate(User $user, mixed $arg1 = null, mixed $arg2 = null): Response
    {
        $targetVersion = $arg1 instanceof ScheduleVersion ? $arg1 : ($arg2 instanceof ScheduleVersion ? $arg2 : null);
        if (!$targetVersion) {
            return Response::deny('Versión de horario no encontrada.');
        }

        $workPeriod = $targetVersion->workPeriod ?? WorkPeriod::withoutGlobalScopes()->find($targetVersion->work_period_id);
        $companyId = $workPeriod?->company_id;
        if ($companyId && $user->company_id !== $companyId && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: La versión de horario pertenece a otra empresa.');
        }

        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value])
            || $user->hasPermission('schedules:manage')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para ejecutar la validación de conflictos.');
    }

    public function resolve(User $user, ScheduleConflict $conflict): Response
    {
        $companyId = $conflict->version?->workPeriod?->company_id;
        if ($companyId && $user->company_id !== $companyId && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El conflicto pertenece a otra empresa.');
        }

        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value]) || $user->hasPermission('schedules:manage')) {
            return Response::allow();
        }

        if ($user->hasRole(RoleCode::MANAGER->value)) {
            // Manager puede resolver si el empleado pertenece a su departamento
            $empDeptId = $conflict->employee?->department_id;
            $userDeptId = $user->employee?->department_id;
            if (!$userDeptId || $empDeptId === $userDeptId) {
                return Response::allow();
            }
            return Response::deny('Acceso denegado: Solo puede resolver conflictos de su propio departamento.');
        }

        return Response::deny('No tiene permisos suficientes para resolver o justificar conflictos.');
    }
}
