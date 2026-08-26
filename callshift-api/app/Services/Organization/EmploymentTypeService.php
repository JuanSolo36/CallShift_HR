<?php

namespace App\Services\Organization;

use App\Models\EmploymentType;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmploymentTypeService
{
    /**
     * Obtiene el listado paginado y filtrado de tipos de empleo/contrato.
     */
    public function listEmploymentTypes(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = EmploymentType::withCount(['employees']);

        // 1. Filtro de búsqueda (nombre o código)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 3. Ordenamiento seguro
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'name', 'code', 'default_weekly_hours', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene la lista compacta de tipos de empleo activos para combos y formularios.
     */
    public function getAllEmploymentTypesCompact(): Collection
    {
        return EmploymentType::where('status', 'ACTIVE')
            ->select(['id', 'name', 'code', 'default_weekly_hours'])
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * Obtiene un tipo de empleo por su ID.
     */
    public function getEmploymentTypeById(int $id): EmploymentType
    {
        return EmploymentType::withCount(['employees'])->findOrFail($id);
    }

    /**
     * Crea un nuevo tipo de empleo/contrato para el tenant del actor.
     */
    public function createEmploymentType(array $data, User $actor): EmploymentType
    {
        return DB::transaction(function () use ($data, $actor) {
            // Forzar contexto del tenant actual
            $data['company_id'] = $actor->company_id;

            $employmentType = EmploymentType::create($data);

            // Registro forense inmutable
            AuditService::logModelCreated(
                $employmentType,
                "Tipo de contrato '{$employmentType->name}' [{$employmentType->code}] creado por '{$actor->username}'"
            );

            return $employmentType;
        });
    }

    /**
     * Actualiza un tipo de empleo existente.
     */
    public function updateEmploymentType(EmploymentType $employmentType, array $data, User $actor): EmploymentType
    {
        return DB::transaction(function () use ($employmentType, $data, $actor) {
            $oldValues = $employmentType->getOriginal();

            // Descartar intentos de modificar el tenant
            unset($data['id'], $data['company_id']);

            $employmentType->fill($data);
            $employmentType->save();

            // Registro forense inmutable
            AuditService::logModelUpdated(
                $employmentType,
                $oldValues,
                "Tipo de contrato '{$employmentType->name}' [{$employmentType->code}] actualizado por '{$actor->username}'"
            );

            return $employmentType;
        });
    }

    /**
     * Elimina un tipo de empleo garantizando integridad referencial.
     */
    public function deleteEmploymentType(EmploymentType $employmentType, User $actor): void
    {
        DB::transaction(function () use ($employmentType, $actor) {
            // Validación de integridad referencial: No eliminar si tiene colaboradores vinculados
            $employeesCount = $employmentType->employees()->count();
            if ($employeesCount > 0) {
                throw ValidationException::withMessages([
                    'employment_type' => "No se puede eliminar el tipo de contrato '{$employmentType->name}' porque tiene {$employeesCount} empleado(s) vinculados.",
                ]);
            }

            // Eliminación lógica (SoftDelete)
            $employmentType->delete();

            // Registrar auditoría de eliminación
            AuditService::logModelDeleted(
                $employmentType,
                "Tipo de contrato '{$employmentType->name}' [{$employmentType->code}] eliminado por '{$actor->username}'"
            );
        });
    }
}
