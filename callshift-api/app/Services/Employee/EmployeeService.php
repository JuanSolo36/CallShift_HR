<?php

namespace App\Services\Employee;

use App\Models\Employee;
use App\Models\User;
use App\Models\Department;
use App\Models\Position;
use App\Models\EmploymentType;
use App\Enums\EmployeeStatus;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    /**
     * Obtiene el listado paginado y filtrado de colaboradores del tenant.
     */
    public function listEmployees(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Employee::with([
            'department',
            'position',
            'employmentType',
            'supervisor',
            'user.role',
        ]);

        // 1. Filtro de búsqueda (nombre, apellido, código, documento, email)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('employee_code', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por departamento
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // 3. Filtro por cargo
        if (!empty($filters['position_id'])) {
            $query->where('position_id', $filters['position_id']);
        }

        // 4. Filtro por tipo de contrato
        if (!empty($filters['employment_type_id'])) {
            $query->where('employment_type_id', $filters['employment_type_id']);
        }

        // 5. Filtro por estado laboral
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 6. Ordenamiento seguro
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'employee_code', 'first_name', 'last_name', 'hire_date', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene lista compacta de colaboradores activos para selectores de supervisor.
     */
    public function getAllEmployeesCompact(?int $excludeId = null): Collection
    {
        $query = Employee::where('status', EmployeeStatus::ACTIVE->value)
            ->select(['id', 'employee_code', 'first_name', 'last_name', 'email']);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->orderBy('first_name', 'asc')->get();
    }

    /**
     * Obtiene un empleado por su ID con todas sus relaciones cargadas.
     */
    public function getEmployeeById(int $id): Employee
    {
        return Employee::with([
            'department',
            'position',
            'employmentType',
            'supervisor',
            'user.role',
        ])->findOrFail($id);
    }

    /**
     * Crea un nuevo empleado dentro del tenant del usuario autenticado.
     */
    public function createEmployee(array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($data, $actor) {
            // 1. Forzar pertenencia al tenant actual
            $data['company_id'] = $actor->company_id;

            // 2. Validación cruzada de seguridad IDOR para relaciones foráneas
            $this->validateForeignEntitiesMatchTenant($data, $actor->company_id);

            // 3. Crear empleado
            $employee = Employee::create($data);
            $employee->load(['department', 'position', 'employmentType', 'supervisor']);

            // 4. Registro forense inmutable
            AuditService::logModelCreated(
                $employee,
                "Expediente de empleado '{$employee->full_name}' [{$employee->employee_code}] creado por '{$actor->username}'"
            );

            return $employee;
        });
    }

    /**
     * Actualiza un empleado existente.
     */
    public function updateEmployee(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $oldValues = $employee->getOriginal();

            // 1. Descartar intentos de modificar el tenant o ID
            unset($data['id'], $data['company_id']);

            // 2. Prevenir auto-supervisión
            if (!empty($data['supervisor_id']) && (int)$data['supervisor_id'] === $employee->id) {
                throw ValidationException::withMessages([
                    'supervisor_id' => 'Un empleado no puede ser su propio supervisor.',
                ]);
            }

            // 3. Validación cruzada de seguridad IDOR para relaciones foráneas
            $this->validateForeignEntitiesMatchTenant($data, $actor->company_id);

            // 4. Actualizar registro
            $employee->fill($data);
            $employee->save();

            $employee->load(['department', 'position', 'employmentType', 'supervisor', 'user.role']);

            // 5. Registro forense inmutable
            AuditService::logModelUpdated(
                $employee,
                $oldValues,
                "Expediente de empleado '{$employee->full_name}' [{$employee->employee_code}] actualizado por '{$actor->username}'"
            );

            return $employee;
        });
    }

    /**
     * Cambia el estado laboral del empleado (Activar, Suspender, Retirar).
     */
    public function changeEmployeeStatus(Employee $employee, string $status, User $actor, ?string $reason = null): Employee
    {
        return DB::transaction(function () use ($employee, $status, $actor, $reason) {
            $oldValues = $employee->getOriginal();

            $updateData = ['status' => $status];

            if ($status === EmployeeStatus::TERMINATED->value) {
                if (empty($employee->termination_date)) {
                    $updateData['termination_date'] = now()->toDateString();
                }
            } elseif ($status === EmployeeStatus::ACTIVE->value) {
                $updateData['termination_date'] = null;
            }

            $employee->fill($updateData);
            $employee->save();

            $employee->load(['department', 'position', 'employmentType', 'supervisor', 'user.role']);

            $detail = "Estado laboral del empleado '{$employee->full_name}' modificado a '{$status}' por '{$actor->username}'";
            if ($reason) {
                $detail .= ". Motivo: {$reason}";
            }

            AuditService::logModelUpdated($employee, $oldValues, $detail);

            return $employee;
        });
    }

    /**
     * Elimina lógicamente un empleado comprobando integridad referencial previa.
     */
    public function deleteEmployee(Employee $employee, User $actor): void
    {
        DB::transaction(function () use ($employee, $actor) {
            // Validación de integridad: Verificar si es supervisor directo de colaboradores activos
            $subordinatesCount = $employee->subordinates()->where('status', EmployeeStatus::ACTIVE->value)->count();
            if ($subordinatesCount > 0) {
                throw ValidationException::withMessages([
                    'employee' => "No se puede eliminar al empleado '{$employee->full_name}' porque tiene {$subordinatesCount} colaborador(es) activo(s) bajo su supervisión directa.",
                ]);
            }

            // Desvincular usuario si existe
            if ($employee->user) {
                $employee->user->update(['employee_id' => null]);
            }

            // Ejecutar soft delete
            $employee->delete();

            // Registrar auditoría forense
            AuditService::logModelDeleted(
                $employee,
                "Expediente de empleado '{$employee->full_name}' [{$employee->employee_code}] eliminado por '{$actor->username}'"
            );
        });
    }

    /**
     * Valida que las entidades relacionadas foráneas pertenezcan al mismo tenant.
     */
    protected function validateForeignEntitiesMatchTenant(array $data, int $companyId): void
    {
        if (!empty($data['department_id'])) {
            $dept = Department::where('company_id', $companyId)->find($data['department_id']);
            if (!$dept) {
                throw ValidationException::withMessages([
                    'department_id' => 'El departamento seleccionado no pertenece a su empresa.',
                ]);
            }
        }

        if (!empty($data['position_id'])) {
            $pos = Position::where('company_id', $companyId)->find($data['position_id']);
            if (!$pos) {
                throw ValidationException::withMessages([
                    'position_id' => 'El cargo seleccionado no pertenece a su empresa.',
                ]);
            }
        }

        if (!empty($data['employment_type_id'])) {
            $type = EmploymentType::where('company_id', $companyId)->find($data['employment_type_id']);
            if (!$type) {
                throw ValidationException::withMessages([
                    'employment_type_id' => 'El tipo de contrato seleccionado no pertenece a su empresa.',
                ]);
            }
        }

        if (!empty($data['supervisor_id'])) {
            $sup = Employee::where('company_id', $companyId)->find($data['supervisor_id']);
            if (!$sup) {
                throw ValidationException::withMessages([
                    'supervisor_id' => 'El supervisor seleccionado no pertenece a su empresa.',
                ]);
            }
        }
    }
}
