<?php

namespace App\Services\Organization;

use App\Models\Position;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PositionService
{
    /**
     * Obtiene el listado paginado y filtrado de cargos del tenant.
     */
    public function listPositions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Position::with(['department'])
            ->withCount(['employees']);

        // 1. Filtro de búsqueda (nombre o código de cargo)
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 2. Filtro por departamento
        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        // 3. Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 4. Ordenamiento seguro
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'name', 'code', 'department_id', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene la lista completa de cargos activos para selectores y combos.
     */
    public function getAllPositionsCompact(?int $departmentId = null): Collection
    {
        $query = Position::where('status', 'ACTIVE')
            ->select(['id', 'department_id', 'name', 'code']);

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query->orderBy('name', 'asc')->get();
    }

    /**
     * Obtiene un cargo por su ID.
     */
    public function getPositionById(int $id): Position
    {
        return Position::with(['department'])
            ->withCount(['employees'])
            ->findOrFail($id);
    }

    /**
     * Crea un nuevo cargo dentro del tenant del usuario autenticado.
     */
    public function createPosition(array $data, User $actor): Position
    {
        return DB::transaction(function () use ($data, $actor) {
            // Forzar contexto del tenant actual
            $data['company_id'] = $actor->company_id;

            $position = Position::create($data);
            $position->load(['department']);

            // Registro forense inmutable
            AuditService::logModelCreated(
                $position,
                "Cargo '{$position->name}' [{$position->code}] creado por '{$actor->username}'"
            );

            return $position;
        });
    }

    /**
     * Actualiza un cargo existente.
     */
    public function updatePosition(Position $position, array $data, User $actor): Position
    {
        return DB::transaction(function () use ($position, $data, $actor) {
            $oldValues = $position->getOriginal();

            // Descartar intentos de modificar el tenant
            unset($data['id'], $data['company_id']);

            $position->fill($data);
            $position->save();

            $position->load(['department']);

            // Registro forense inmutable
            AuditService::logModelUpdated(
                $position,
                $oldValues,
                "Cargo '{$position->name}' [{$position->code}] actualizado por '{$actor->username}'"
            );

            return $position;
        });
    }

    /**
     * Elimina un cargo comprobando integridad referencial previa.
     */
    public function deletePosition(Position $position, User $actor): void
    {
        DB::transaction(function () use ($position, $actor) {
            // Validación de integridad referencial: No eliminar si tiene empleados asignados
            $employeesCount = $position->employees()->count();
            if ($employeesCount > 0) {
                throw ValidationException::withMessages([
                    'position' => "No se puede eliminar el cargo '{$position->name}' porque tiene {$employeesCount} empleado(s) vinculados.",
                ]);
            }

            // Ejecutar eliminación lógica
            $position->delete();

            // Registrar auditoría de eliminación
            AuditService::logModelDeleted(
                $position,
                "Cargo '{$position->name}' [{$position->code}] eliminado por '{$actor->username}'"
            );
        });
    }
}
