<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Determina si el actor puede ver el listado de usuarios del tenant.
     */
    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('users:view') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value]);
    }

    /**
     * Determina si el actor puede consultar los detalles de un usuario específico.
     */
    public function view(User $actor, User $target): Response
    {
        // Regla multi-tenant inviolable: Mismo tenant
        if ($actor->company_id !== $target->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El usuario pertenece a otra empresa.');
        }

        // Permitir si es su propio perfil o si tiene permiso de visualización
        if ($actor->id === $target->id || $actor->hasPermission('users:view') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permiso para ver detalles de usuarios.');
    }

    /**
     * Determina si el actor puede crear nuevos usuarios en el tenant.
     */
    public function create(User $actor): bool
    {
        return $actor->hasPermission('users:create') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value]);
    }

    /**
     * Determina si el actor puede actualizar la información de un usuario.
     */
    public function update(User $actor, User $target): Response
    {
        // Regla multi-tenant inviolable
        if ($actor->company_id !== $target->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede modificar usuarios de otra empresa.');
        }

        if ($actor->hasPermission('users:update') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para actualizar este usuario.');
    }

    /**
     * Determina si el actor puede cambiar el estado (activar/desactivar/suspender) de un usuario.
     */
    public function changeStatus(User $actor, User $target): Response
    {
        // Regla multi-tenant
        if ($actor->company_id !== $target->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: El usuario pertenece a otra empresa.');
        }

        // Bloquear auto-desactivación (prevenir lock-out administrativo)
        if ($actor->id === $target->id) {
            return Response::deny('No puede cambiar el estado de su propia cuenta.');
        }

        if ($actor->hasPermission('users:manage') || $actor->hasPermission('users:update') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para modificar el estado de usuarios.');
    }

    /**
     * Determina si el actor puede eliminar (soft delete) a un usuario.
     */
    public function delete(User $actor, User $target): Response
    {
        // Regla multi-tenant
        if ($actor->company_id !== $target->company_id && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Acceso denegado: No puede eliminar usuarios de otra empresa.');
        }

        // Bloquear auto-eliminación
        if ($actor->id === $target->id) {
            return Response::deny('No puede eliminar su propia cuenta de usuario.');
        }

        // Bloquear eliminación del Super Administrador principal
        if ($target->hasRole(RoleCode::SUPER_ADMIN->value) && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('Solo un Super Administrador puede eliminar a otro Super Administrador.');
        }

        if ($actor->hasPermission('users:delete') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para eliminar usuarios.');
    }

    /**
     * Determina si el actor puede asignar o modificar el rol de un usuario.
     */
    public function assignRole(User $actor, User $target, int $newRoleId): Response
    {
        // Bloquear auto-escalada de privilegios
        if ($actor->id === $target->id && $actor->role_id !== $newRoleId) {
            return Response::deny('No puede modificar o elevar su propio rol.');
        }

        $targetRole = Role::find($newRoleId);
        if (!$targetRole) {
            return Response::deny('El rol seleccionado no existe.');
        }

        // Un HR_ADMIN u otro rol no puede crear o asignar el rol SUPER_ADMIN
        if ($targetRole->code === RoleCode::SUPER_ADMIN->value && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return Response::deny('No tiene autorización para asignar el rol de Super Administrador.');
        }

        if ($actor->hasPermission('users:manage') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value])) {
            return Response::allow();
        }

        return Response::deny('No tiene permisos para asignar roles.');
    }
}
