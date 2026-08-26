<?php

namespace App\Services\Shifts;

use App\Models\ShiftType;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftTypeService
{
    /**
     * Obtiene listado paginado y filtrado de tipos de turno.
     */
    public function listShiftTypes(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = ShiftType::withCount(['assignments']);

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
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'name', 'code', 'start_time', 'total_work_hours', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'id';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene la lista compacta de turnos activos para la malla de planificación.
     */
    public function getAllShiftTypesCompact(): Collection
    {
        return ShiftType::where('status', 'ACTIVE')
            ->select([
                'id',
                'name',
                'code',
                'color_hex',
                'start_time',
                'end_time',
                'break_duration_minutes',
                'total_work_hours',
                'crosses_midnight',
            ])
            ->orderBy('start_time', 'asc')
            ->get();
    }

    /**
     * Obtiene un tipo de turno por ID.
     */
    public function getShiftTypeById(int $id): ShiftType
    {
        return ShiftType::withCount(['assignments'])->findOrFail($id);
    }

    /**
     * Registra un nuevo tipo de turno calculando coherentemente la jornada y el cruce de medianoche.
     */
    public function createShiftType(array $data, User $actor): ShiftType
    {
        return DB::transaction(function () use ($data, $actor) {
            // Forzar contexto del tenant
            $data['company_id'] = $actor->company_id;

            // Procesar y calcular propiedades temporales
            $this->normalizeAndComputeShiftData($data);

            $shiftType = ShiftType::create($data);

            // Registro forense inmutable
            AuditService::logModelCreated(
                $shiftType,
                "Tipo de turno '{$shiftType->name}' [{$shiftType->code}] ({$shiftType->start_time} - {$shiftType->end_time}) creado por '{$actor->username}'"
            );

            return $shiftType;
        });
    }

    /**
     * Actualiza un tipo de turno existente recalculando su jornada.
     */
    public function updateShiftType(ShiftType $shiftType, array $data, User $actor): ShiftType
    {
        return DB::transaction(function () use ($shiftType, $data, $actor) {
            $oldValues = $shiftType->getOriginal();

            // Descartar modificaciones al tenant o ID
            unset($data['id'], $data['company_id']);

            // Fusionar con datos actuales para recalcular si se modifican horas o breaks
            $merged = array_merge([
                'start_time'             => substr($shiftType->start_time, 0, 5),
                'end_time'               => substr($shiftType->end_time, 0, 5),
                'break_duration_minutes' => $shiftType->break_duration_minutes,
                'crosses_midnight'       => $shiftType->crosses_midnight,
                'total_work_hours'       => $shiftType->total_work_hours,
            ], $data);

            $this->normalizeAndComputeShiftData($merged);

            $shiftType->fill($merged);
            $shiftType->save();

            // Registro forense inmutable
            AuditService::logModelUpdated(
                $shiftType,
                $oldValues,
                "Tipo de turno '{$shiftType->name}' [{$shiftType->code}] actualizado por '{$actor->username}'"
            );

            return $shiftType;
        });
    }

    /**
     * Elimina lógicamente un tipo de turno verificando integridad previa.
     */
    public function deleteShiftType(ShiftType $shiftType, User $actor): void
    {
        DB::transaction(function () use ($shiftType, $actor) {
            // Integridad referencial: Comprobar asignaciones previas en mallas
            $assignmentsCount = $shiftType->assignments()->count();
            if ($assignmentsCount > 0) {
                throw ValidationException::withMessages([
                    'shift_type' => "No se puede eliminar el tipo de turno '{$shiftType->name}' porque ya cuenta con {$assignmentsCount} asignación(es) en mallas de turnos.",
                ]);
            }

            $shiftType->delete();

            // Registro forense inmutable
            AuditService::logModelDeleted(
                $shiftType,
                "Tipo de turno '{$shiftType->name}' [{$shiftType->code}] eliminado por '{$actor->username}'"
            );
        });
    }

    /**
     * Normaliza horarios, determina si cruza medianoche y calcula horas efectivas.
     */
    public function normalizeAndComputeShiftData(array &$data): void
    {
        if (empty($data['start_time']) || empty($data['end_time'])) {
            return;
        }

        $startStr = substr($data['start_time'], 0, 5);
        $endStr = substr($data['end_time'], 0, 5);

        [$startH, $startM] = explode(':', $startStr);
        [$endH, $endM] = explode(':', $endStr);

        $startMinutes = (int)$startH * 60 + (int)$startM;
        $endMinutes = (int)$endH * 60 + (int)$endM;

        $breakMinutes = isset($data['break_duration_minutes']) ? (int)$data['break_duration_minutes'] : 60;

        if ($endMinutes < $startMinutes) {
            // Cruza medianoche (Ej: 22:00 -> 06:00 = 1320 -> 360)
            $data['crosses_midnight'] = true;
            $rawDurationMinutes = (1440 - $startMinutes) + $endMinutes;
        } elseif ($endMinutes > $startMinutes) {
            // Turno normal diurno/vespertino
            $data['crosses_midnight'] = false;
            $rawDurationMinutes = $endMinutes - $startMinutes;
        } else {
            // Horas iguales (00:00 -> 00:00 o 08:00 -> 08:00)
            // Si crosses_midnight es true -> 24 horas continuas (1440 min)
            if (!empty($data['crosses_midnight'])) {
                $rawDurationMinutes = 1440;
                $data['crosses_midnight'] = true;
            } else {
                $rawDurationMinutes = 0;
                $data['crosses_midnight'] = false;
            }
        }

        // Si no se proveyó un total_work_hours explícito, calcularlo automáticamente
        if (empty($data['total_work_hours'])) {
            $effectiveMinutes = max(0, $rawDurationMinutes - $breakMinutes);
            $data['total_work_hours'] = round($effectiveMinutes / 60, 2);
        }
    }
}
