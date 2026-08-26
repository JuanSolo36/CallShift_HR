<?php

namespace App\Policies;

use App\Enums\RoleCode;
use App\Models\BusinessRule;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class BusinessRulePolicy
{
    public function viewAny(User $user): Response
    {
        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])
            || $user->hasPermission('business_rules:view')
            || $user->hasPermission('settings:view')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para consultar reglas de negocio.');
    }

    public function view(User $user, BusinessRule $rule): Response
    {
        if ($user->company_id !== $rule->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: La regla de negocio pertenece a otra empresa.');
        }

        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value, RoleCode::SUPERVISOR->value, RoleCode::VIEWER->value])
            || $user->hasPermission('business_rules:view')
            || $user->hasPermission('settings:view')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para ver esta regla de negocio.');
    }

    public function create(User $user): Response
    {
        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])
            || $user->hasPermission('business_rules:manage')
            || $user->hasPermission('settings:manage')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para crear o configurar reglas de negocio.');
    }

    public function update(User $user, BusinessRule $rule): Response
    {
        if ($user->company_id !== $rule->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar reglas de negocio de otra empresa.');
        }

        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])
            || $user->hasPermission('business_rules:manage')
            || $user->hasPermission('settings:manage')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para modificar reglas de negocio.');
    }

    public function delete(User $user, BusinessRule $rule): Response
    {
        if ($user->company_id !== $rule->company_id && !$user->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar reglas de negocio de otra empresa.');
        }

        if ($user->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])
            || $user->hasPermission('business_rules:manage')
            || $user->hasPermission('settings:manage')) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para eliminar reglas de negocio.');
    }
}
