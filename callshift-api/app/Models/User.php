<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable, SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'role_id',
        'username',
        'email',
        'password',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
        'password'      => 'hashed',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function hasRole(string|array $roles): bool
    {
        if (!$this->role) return false;
        if (is_string($roles)) {
            return $this->role->code === $roles;
        }
        return in_array($this->role->code, $roles, true);
    }

    public function hasPermission(string $permissionCode): bool
    {
        if ($this->hasRole('SUPER_ADMIN')) {
            return true; // Super admin bypass
        }
        return $this->role?->hasPermission($permissionCode) ?? false;
    }
}
