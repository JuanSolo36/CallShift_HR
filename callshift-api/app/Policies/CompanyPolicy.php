<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Company;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class CompanyPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el actor puede ver la información de la empresa.
     */
    public function view(User $actor, Company $company): Response
    {
        if ($actor->company_id !== $company->id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede consultar información de otra empresa.');
        }

        if ($actor->hasPermission('company:view') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value, RoleCode::MANAGER->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para ver los datos de la empresa.');
    }

    /**
     * Determina si el actor puede actualizar la información corporativa de la empresa.
     */
    public function update(User $actor, Company $company): Response
    {
        if ($actor->company_id !== $company->id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar datos de otra empresa.');
        }

        if ($actor->hasPermission('company:update') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para actualizar la información de la empresa.');
    }

    /**
     * Determina si el actor puede gestionar la configuración regional y visual de la empresa.
     */
    public function manageSettings(User $actor, Company $company): Response
    {
        if ($actor->company_id !== $company->id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar la configuración de otra empresa.');
        }

        if ($actor->hasPermission('settings:manage') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para modificar la configuración de la empresa.');
    }

    /**
     * Determina si el actor puede eliminar una empresa (solo Super Administrador).
     */
    public function delete(User $actor, Company $company): Response
    {
        if (!$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Solo un Super Administrador puede eliminar una empresa.');
        }

        return Response::allow();
    }
}
