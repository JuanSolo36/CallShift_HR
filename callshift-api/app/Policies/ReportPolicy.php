<?php

namespace App\Policies;

use App\Models\User;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class ReportPolicy
{
    use HandlesAuthorization;

    public function view(User $actor, string $reportType = ''): Response
    {
        if ($actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value]) 
            || $actor->hasPermission('reports:view')
            || $actor->hasPermission('schedules:view')) {
            return Response::allow();
        }

        if ($actor->hasRole(RoleCode::SUPERVISOR->value) && in_array($reportType, ['employees', 'schedules', 'hours', 'absences', 'modifications'], true)) {
            return Response::allow();
        }

        if ($actor->hasRole(RoleCode::VIEWER->value) && in_array($reportType, ['employees', 'schedules', 'hours', 'absences'], true)) {
            return Response::allow();
        }

        return Response::deny('No tiene autorización para consultar este reporte empresarial.');
    }

    public function export(User $actor, string $reportType = ''): Response
    {
        if ($actor->hasRole(RoleCode::VIEWER->value) || $actor->hasRole(RoleCode::EMPLOYEE->value)) {
            return Response::deny('Los usuarios con rol VIEWER o EMPLOYEE no tienen permisos para exportar reportes.');
        }

        if ($actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value]) 
            || $actor->hasPermission('reports:export')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para exportar este reporte.');
    }
}
