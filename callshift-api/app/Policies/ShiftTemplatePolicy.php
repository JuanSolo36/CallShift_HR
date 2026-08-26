<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ShiftTemplate;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\Response;

class ShiftTemplatePolicy
{
    public function viewAny(User $user): Response
    {
        if ($user->hasPermission('shifts:view') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para consultar plantillas de turno.');
    }

    public function view(User $user, ShiftTemplate $template): Response
    {
        if ($user->company_id !== $template->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: La plantilla pertenece a otra empresa.');
        }

        if ($user->hasPermission('shifts:view') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para ver esta plantilla.');
    }

    public function create(User $user): Response
    {
        if ($user->hasPermission('shifts:manage') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para crear plantillas de turno.');
    }

    public function update(User $user, ShiftTemplate $template): Response
    {
        if ($user->company_id !== $template->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar plantillas de otra empresa.');
        }

        if ($user->hasPermission('shifts:manage') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para modificar esta plantilla.');
    }

    public function delete(User $user, ShiftTemplate $template): Response
    {
        if ($user->company_id !== $template->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar plantillas de otra empresa.');
        }

        if ($user->hasPermission('shifts:manage') || $user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para eliminar esta plantilla.');
    }
}
