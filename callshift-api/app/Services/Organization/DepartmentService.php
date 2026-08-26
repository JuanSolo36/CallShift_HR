<?php

namespace App\Services\Organization;

use App\Models\Department;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DepartmentService
{
    /**
     * Obtiene el listado paginado y filtrado de departamentos del tenant.
     */
    public function listDepartments(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Department::with(['manager'])
            ->withCount(['positions', 'employees']);

        // 1. Filtro de búsqueda (nombre, código o centro de costo)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('cost_center_code', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 3. Ordenamiento seguro
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'name', 'code', 'cost_center_code', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene la lista completa de departamentos activos para selectores y combos.
     */
    public function getAllDepartmentsCompact(): Collection
    {
        return Department::where('status', 'ACTIVE')
            ->select(['id', 'name', 'code', 'cost_center_code'])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Obtiene un departamento por su ID.
     */
    public function getDepartmentById(int $id): Department
    {
        return Department::with(['manager'])
            ->withCount(['positions', 'employees'])
            ->findOrFail($id);
    }

    /**
     * Crea un nuevo departamento dentro del tenant del usuario autenticado.
     */
    public function createDepartment(array $data, User $actor): Department
    {
        return DB::transaction(function () use ($data, $actor) {
            // Forzar contexto del tenant actual
            $data['company_id'] = $actor->company_id;

            $department = Department::create($data);
            $department->load(['manager']);

            // Registro forense inmutable
            AuditService::logModelCreated(
                $department,
                "Departamento '{$department->name}' [{$department->code}] creado por '{$actor->username}'"
            );

            return $department;
        });
    }

    /**
     * Actualiza un departamento existente.
     */
    public function updateDepartment(Department $department, array $data, User $actor): Department
    {
        return DB::transaction(function () use ($department, $data, $actor) {
            $oldValues = $department->getOriginal();

            // Descartar intentos de modificar el tenant
            unset($data['id'], $data['company_id']);

            $department->fill($data);
            $department->save();

            $department->load(['manager']);

            // Registro forense inmutable
            AuditService::logModelUpdated(
                $department,
                $oldValues,
                "Departamento '{$department->name}' [{$department->code}] actualizado por '{$actor->username}'"
            );

            return $department;
        });
    }

    /**
     * Elimina un departamento comprobando integridad referencial previa.
     */
    public function deleteDepartment(Department $department, User $actor): void
    {
        DB::transaction(function () use ($department, $actor) {
            // Validación de integridad referencial: No eliminar si tiene cargos o empleados asignados
            $positionsCount = $department->positions()->count();
            if ($positionsCount > 0) {
                throw ValidationException::withMessages([
                    'department' => "No se puede eliminar el departamento '{$department->name}' porque contiene {$positionsCount} cargo(s) asociados.",
                ]);
            }

            $employeesCount = $department->employees()->count();
            if ($employeesCount > 0) {
                throw ValidationException::withMessages([
                    'department' => "No se puede eliminar el departamento '{$department->name}' porque contiene {$employeesCount} empleado(s) vinculados.",
                ]);
            }

            // Ejecutar eliminación lógica
            $department->delete();

            // Registrar auditoría de eliminación
            AuditService::logModelDeleted(
                $department,
                "Departamento '{$department->name}' [{$department->code}] eliminado por '{$actor->username}'"
            );
        });
    }
}
