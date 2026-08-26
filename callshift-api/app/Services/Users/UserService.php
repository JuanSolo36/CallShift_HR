<?php

namespace App\Services\Users;

use App\Models\User;
use App\Models\Role;
use App\Enums\AuditAction;
use App\Enums\RoleCode;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    /**
     * Obtiene el listado paginado y filtrado de usuarios dentro del tenant.
     */
    public function listUsers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::with([
            'role.permissions',
            'employee.department',
            'employee.position',
            'company',
        ]);

        // 1. Filtro de búsqueda (username, email o nombre de empleado)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhereHas('employee', function ($eq) use ($search) {
                      $eq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%")
                         ->orWhere('employee_code', 'like', "%{$search}%");
                  });
            });
        }

        // 2. Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 3. Filtro por rol
        if (!empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        // 4. Filtro por departamento
        if (!empty($filters['department_id'])) {
            $query->whereHas('employee', function ($q) use ($filters) {
                $q->where('department_id', $filters['department_id']);
            });
        }

        // 5. Ordenamiento seguro
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'username', 'email', 'created_at', 'last_login_at', 'status'], true)
            ? $filters['sort_by']
            : 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene un usuario por ID.
     */
    public function getUserById(int $id): User
    {
        return User::with([
            'role.permissions',
            'employee.department',
            'employee.position',
            'company',
        ])->findOrFail($id);
    }

    /**
     * Crea un nuevo usuario en el tenant del actor.
     */
    public function createUser(array $data, User $actor): User
    {
        return DB::transaction(function () use ($data, $actor) {
            // Prevenir asignación del rol SUPER_ADMIN por un usuario no SUPER_ADMIN
            if (!empty($data['role_id'])) {
                $role = Role::findOrFail($data['role_id']);
                if ($role->code === RoleCode::SUPER_ADMIN->value && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
                    throw ValidationException::withMessages([
                        'role_id' => 'No tiene permisos para asignar el rol de Super Administrador.',
                    ]);
                }
            }

            // Forzar contexto del tenant actual (ignora cualquier company_id manipulado)
            $userData = [
                'company_id'  => $actor->company_id,
                'username'    => $data['username'],
                'email'       => $data['email'],
                'password'    => Hash::make($data['password']),
                'role_id'     => $data['role_id'],
                'employee_id' => $data['employee_id'] ?? null,
                'status'      => $data['status'] ?? 'ACTIVE',
            ];

            $user = User::create($userData);

            // Cargar relaciones
            $user->load(['role.permissions', 'employee.department', 'employee.position', 'company']);

            // Auditoría forense segura (sin registrar passwords ni secrets)
            AuditService::logModelCreated(
                $user,
                "Usuario '{$user->username}' ({$user->email}) creado por '{$actor->username}'"
            );

            return $user;
        });
    }

    /**
     * Actualiza un usuario existente.
     */
    public function updateUser(User $target, array $data, User $actor): User
    {
        return DB::transaction(function () use ($target, $data, $actor) {
            $oldValues = $target->getOriginal();

            // Bloqueo de auto-escalada de privilegios
            if (!empty($data['role_id']) && $data['role_id'] != $target->role_id) {
                if ($actor->id === $target->id) {
                    throw ValidationException::withMessages([
                        'role_id' => 'No está permitido modificar o elevar su propio rol.',
                    ]);
                }

                $newRole = Role::findOrFail($data['role_id']);
                if ($newRole->code === RoleCode::SUPER_ADMIN->value && !$actor->hasRole(RoleCode::SUPER_ADMIN->value)) {
                    throw ValidationException::withMessages([
                        'role_id' => 'Solo un Super Administrador puede asignar este rol.',
                    ]);
                }
                $target->role_id = $data['role_id'];
            }

            if (isset($data['username'])) {
                $target->username = $data['username'];
            }

            if (isset($data['email'])) {
                $target->email = $data['email'];
            }

            if (isset($data['employee_id'])) {
                $target->employee_id = $data['employee_id'];
            }

            if (isset($data['status'])) {
                if ($actor->id === $target->id && $data['status'] !== 'ACTIVE') {
                    throw ValidationException::withMessages([
                        'status' => 'No puede desactivar o suspender su propia cuenta.',
                    ]);
                }
                $target->status = $data['status'];
            }

            // Actualización segura de contraseña si fue enviada
            if (!empty($data['password'])) {
                $target->password = Hash::make($data['password']);
                // Revocar tokens activos para forzar re-autenticación
                $target->tokens()->delete();
            }

            $target->save();

            // Auditoría forense segura
            AuditService::logModelUpdated(
                $target,
                $oldValues,
                "Usuario '{$target->username}' actualizado por '{$actor->username}'"
            );

            $target->load(['role.permissions', 'employee.department', 'employee.position', 'company']);

            return $target;
        });
    }

    /**
     * Modifica el estado de un usuario (ACTIVE, INACTIVE, SUSPENDED).
     */
    public function changeUserStatus(User $target, string $status, User $actor, ?string $reason = null): User
    {
        return DB::transaction(function () use ($target, $status, $actor, $reason) {
            if ($actor->id === $target->id) {
                throw ValidationException::withMessages([
                    'status' => 'No puede modificar el estado de su propia cuenta.',
                ]);
            }

            $oldStatus = $target->status;
            $target->status = $status;
            $target->save();

            // Si se desactiva o suspende, revocar tokens inmediatamente
            if ($status !== 'ACTIVE') {
                $target->tokens()->delete();
            }

            $desc = "Estado de usuario '{$target->username}' cambiado de {$oldStatus} a {$status} por '{$actor->username}'";
            if ($reason) {
                $desc .= ". Motivo: {$reason}";
            }

            AuditService::log(
                AuditAction::UPDATE,
                $target,
                ['status' => $oldStatus],
                ['status' => $status, 'reason' => $reason],
                $desc
            );

            $target->load(['role.permissions', 'employee.department', 'employee.position', 'company']);

            return $target;
        });
    }

    /**
     * Elimina lógicamente (soft delete) a un usuario garantizando consistencia transaccional.
     */
    public function deleteUser(User $target, User $actor): void
    {
        if ($actor->id === $target->id) {
            throw ValidationException::withMessages([
                'user' => 'No puede eliminar su propia cuenta de usuario.',
            ]);
        }

        DB::transaction(function () use ($target, $actor) {
            // 1. Revocar tokens de acceso del usuario eliminado
            $target->tokens()->delete();

            // 2. Ejecutar eliminación lógica (SoftDelete)
            $target->delete();

            // 3. Registrar auditoría forense dentro de la misma transacción confirmada
            AuditService::logModelDeleted(
                $target,
                "Usuario '{$target->username}' ({$target->email}) eliminado por '{$actor->username}'"
            );
        });
    }
}
