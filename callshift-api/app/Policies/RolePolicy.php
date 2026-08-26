<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Role;
use App\Enums\RoleCode;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $actor): bool
    {
        return $actor->hasPermission('roles:view') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value]);
    }

    public function view(User $actor, Role $role): bool
    {
        // Los roles globales del sistema (company_id === null) son visibles por cualquier tenant autorizado
        if ($role->company_id === null || $role->company_id === $actor->company_id || $actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
            return $actor->hasPermission('roles:view') || $actor->hasRole([RoleCode::SUPER_ADMIN->value, RoleCode::HR_ADMIN->value]);
        }

        return false;
    }
}
