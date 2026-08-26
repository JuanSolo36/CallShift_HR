<?php

namespace App\Services\Roles;

use App\Models\Role;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class RoleService
{
    /**
     * Lista todos los roles disponibles para el tenant.
     */
    public function listRoles(User $actor): Collection
    {
        return Role::with('permissions')
            ->where(function ($q) use ($actor) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $actor->company_id);
            })
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * Obtiene un rol por ID con sus permisos.
     */
    public function getRoleById(int $id, User $actor): Role
    {
        return Role::with('permissions')
            ->where(function ($q) use ($actor) {
                $q->whereNull('company_id')
                  ->orWhere('company_id', $actor->company_id);
            })
            ->findOrFail($id);
    }

    /**
     * Lista todos los permisos del sistema organizados por módulo.
     */
    public function listPermissions(): Collection
    {
        return Permission::orderBy('module', 'asc')
            ->orderBy('name', 'asc')
            ->get();
    }
}
