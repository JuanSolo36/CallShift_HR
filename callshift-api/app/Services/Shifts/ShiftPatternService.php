<?php

namespace App\Services\Shifts;

use App\Models\User;
use App\Models\ShiftPattern;
use App\Models\ShiftPatternEntry;
use App\Models\ShiftTemplate;
use App\Models\ShiftType;
use App\Enums\DayType;
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ShiftPatternService
{
    /**
     * Lista los patrones de turno de la empresa con filtrado opcional.
     */
    public function listPatterns(User $actor, array $filters = [])
    {
        $query = ShiftPattern::where('company_id', $actor->company_id)
            ->with(['department', 'position', 'entries.shiftType']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        if (!empty($filters['search'])) {
            $term = "%{$filters['search']}%";
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                  ->orWhere('code', 'like', $term);
            });
        }

        return $query->orderBy('name')->get();
    }

    /**
     * Valida la coherencia interna de las entradas del patrón.
     */
    public function validatePatternEntries(int $cycleLength, array $entries, int $companyId): void
    {
        if (count($entries) !== $cycleLength) {
            throw ValidationException::withMessages([
                'entries' => "La cantidad de entradas (" . count($entries) . ") debe coincidir exactamente con la longitud del ciclo ({$cycleLength} días).",
            ]);
        }

        $seenDays = [];
        foreach ($entries as $index => $entry) {
            $dayNumber = (int) ($entry['day_number'] ?? 0);
            if ($dayNumber < 1 || $dayNumber > $cycleLength) {
                throw ValidationException::withMessages([
                    "entries.{$index}.day_number" => "El día {$dayNumber} está fuera del rango permitido (1 a {$cycleLength}).",
                ]);
            }

            if (isset($seenDays[$dayNumber])) {
                throw ValidationException::withMessages([
                    "entries.{$index}.day_number" => "El día {$dayNumber} del ciclo está duplicado en las entradas.",
                ]);
            }
            $seenDays[$dayNumber] = true;

            $dayType = $entry['day_type'] ?? null;
            $shiftTypeId = $entry['shift_type_id'] ?? null;

            if ($dayType === DayType::WORK->value) {
                if (!$shiftTypeId) {
                    throw ValidationException::withMessages([
                        "entries.{$index}.shift_type_id" => "Las entradas de tipo laboral (WORK) requieren un tipo de turno asignado.",
                    ]);
                }

                $shiftType = ShiftType::where('company_id', $companyId)
                    ->where('status', 'ACTIVE')
                    ->find($shiftTypeId);

                if (!$shiftType) {
                    throw ValidationException::withMessages([
                        "entries.{$index}.shift_type_id" => "El turno asignado para el día {$dayNumber} no existe o está inactivo.",
                    ]);
                }
            }
        }
    }

    /**
     * Crea un nuevo patrón con sus entradas dentro de una transacción atómica.
     */
    public function createPattern(array $data, User $actor): ShiftPattern
    {
        $cycleLength = (int) $data['cycle_length_days'];
        $entries     = $data['entries'] ?? [];

        $this->validatePatternEntries($cycleLength, $entries, $actor->company_id);

        return DB::transaction(function () use ($data, $entries, $cycleLength, $actor) {
            $pattern = ShiftPattern::create([
                'company_id'        => $actor->company_id,
                'department_id'     => $data['department_id'] ?? null,
                'position_id'       => $data['position_id'] ?? null,
                'name'              => $data['name'],
                'code'              => strtoupper(trim($data['code'])),
                'cycle_length_days' => $cycleLength,
                'description'       => $data['description'] ?? null,
                'status'            => $data['status'] ?? 'ACTIVE',
                'created_by'        => $actor->id,
            ]);

            foreach ($entries as $entry) {
                ShiftPatternEntry::create([
                    'shift_pattern_id'    => $pattern->id,
                    'day_number'          => (int) $entry['day_number'],
                    'day_type'            => $entry['day_type'],
                    'shift_type_id'       => $entry['day_type'] === DayType::WORK->value ? ($entry['shift_type_id'] ?? null) : null,
                    'start_time_override' => $entry['start_time_override'] ?? null,
                    'end_time_override'   => $entry['end_time_override'] ?? null,
                    'notes'               => $entry['notes'] ?? null,
                ]);
            }

            AuditService::log(
                AuditAction::CREATE,
                ShiftPattern::class,
                $pattern->id,
                "Patrón '{$pattern->name}' creado.",
                null,
                $pattern->toArray(),
                $actor->company_id
            );

            return $pattern->load(['entries.shiftType', 'department', 'position']);
        });
    }

    /**
     * Actualiza un patrón existente y sus entradas de forma atómica.
     */
    public function updatePattern(ShiftPattern $pattern, array $data, User $actor): ShiftPattern
    {
        $cycleLength = (int) ($data['cycle_length_days'] ?? $pattern->cycle_length_days);
        $entries     = $data['entries'] ?? null;

        if ($entries !== null) {
            $this->validatePatternEntries($cycleLength, $entries, $actor->company_id);
        }

        return DB::transaction(function () use ($pattern, $data, $entries, $cycleLength, $actor) {
            $oldValues = $pattern->toArray();

            $pattern->fill([
                'name'              => $data['name'] ?? $pattern->name,
                'code'              => isset($data['code']) ? strtoupper(trim($data['code'])) : $pattern->code,
                'department_id'     => array_key_exists('department_id', $data) ? $data['department_id'] : $pattern->department_id,
                'position_id'       => array_key_exists('position_id', $data) ? $data['position_id'] : $pattern->position_id,
                'cycle_length_days' => $cycleLength,
                'description'       => array_key_exists('description', $data) ? $data['description'] : $pattern->description,
                'status'            => $data['status'] ?? $pattern->status,
            ]);
            $pattern->save();

            if ($entries !== null) {
                // Reemplazar entradas existentes
                $pattern->entries()->delete();
                foreach ($entries as $entry) {
                    ShiftPatternEntry::create([
                        'shift_pattern_id'    => $pattern->id,
                        'day_number'          => (int) $entry['day_number'],
                        'day_type'            => $entry['day_type'],
                        'shift_type_id'       => $entry['day_type'] === DayType::WORK->value ? ($entry['shift_type_id'] ?? null) : null,
                        'start_time_override' => $entry['start_time_override'] ?? null,
                        'end_time_override'   => $entry['end_time_override'] ?? null,
                        'notes'               => $entry['notes'] ?? null,
                    ]);
                }
            }

            AuditService::log(
                AuditAction::UPDATE,
                ShiftPattern::class,
                $pattern->id,
                "Patrón '{$pattern->name}' actualizado.",
                $oldValues,
                $pattern->toArray(),
                $actor->company_id
            );

            return $pattern->load(['entries.shiftType', 'department', 'position']);
        });
    }

    /**
     * Elimina lógicamente un patrón.
     */
    public function deletePattern(ShiftPattern $pattern, User $actor): void
    {
        DB::transaction(function () use ($pattern, $actor) {
            $oldValues = $pattern->toArray();
            $pattern->delete();

            AuditService::log(
                AuditAction::DELETE,
                ShiftPattern::class,
                $pattern->id,
                "Patrón '{$pattern->name}' eliminado.",
                $oldValues,
                null,
                $actor->company_id
            );
        });
    }

    /**
     * CRUD de Plantillas (ShiftTemplate)
     */
    public function listTemplates(User $actor, array $filters = [])
    {
        $query = ShiftTemplate::where('company_id', $actor->company_id)
            ->with(['department', 'position', 'pattern.entries.shiftType']);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['department_id'])) {
            $query->where('department_id', $filters['department_id']);
        }

        return $query->orderBy('name')->get();
    }

    public function createTemplate(array $data, User $actor): ShiftTemplate
    {
        return DB::transaction(function () use ($data, $actor) {
            $template = ShiftTemplate::create([
                'company_id'       => $actor->company_id,
                'department_id'    => $data['department_id'] ?? null,
                'position_id'      => $data['position_id'] ?? null,
                'shift_pattern_id' => $data['shift_pattern_id'] ?? null,
                'name'             => $data['name'],
                'code'             => strtoupper(trim($data['code'])),
                'description'      => $data['description'] ?? null,
                'status'           => $data['status'] ?? 'ACTIVE',
                'metadata'         => $data['metadata'] ?? null,
                'created_by'       => $actor->id,
            ]);

            AuditService::log(
                AuditAction::CREATE,
                ShiftTemplate::class,
                $template->id,
                "Plantilla '{$template->name}' creada.",
                null,
                $template->toArray(),
                $actor->company_id
            );

            return $template->load(['department', 'position', 'pattern.entries.shiftType']);
        });
    }

    public function updateTemplate(ShiftTemplate $template, array $data, User $actor): ShiftTemplate
    {
        return DB::transaction(function () use ($template, $data, $actor) {
            $oldValues = $template->toArray();

            $template->fill([
                'name'             => $data['name'] ?? $template->name,
                'code'             => isset($data['code']) ? strtoupper(trim($data['code'])) : $template->code,
                'department_id'    => array_key_exists('department_id', $data) ? $data['department_id'] : $template->department_id,
                'position_id'      => array_key_exists('position_id', $data) ? $data['position_id'] : $template->position_id,
                'shift_pattern_id' => array_key_exists('shift_pattern_id', $data) ? $data['shift_pattern_id'] : $template->shift_pattern_id,
                'description'      => array_key_exists('description', $data) ? $data['description'] : $template->description,
                'status'           => $data['status'] ?? $template->status,
                'metadata'         => array_key_exists('metadata', $data) ? $data['metadata'] : $template->metadata,
            ]);
            $template->save();

            AuditService::log(
                AuditAction::UPDATE,
                ShiftTemplate::class,
                $template->id,
                "Plantilla '{$template->name}' actualizada.",
                $oldValues,
                $template->toArray(),
                $actor->company_id
            );

            return $template->load(['department', 'position', 'pattern.entries.shiftType']);
        });
    }

    public function deleteTemplate(ShiftTemplate $template, User $actor): void
    {
        DB::transaction(function () use ($template, $actor) {
            $oldValues = $template->toArray();
            $template->delete();

            AuditService::log(
                AuditAction::DELETE,
                ShiftTemplate::class,
                $template->id,
                "Plantilla '{$template->name}' eliminada.",
                $oldValues,
                null,
                $actor->company_id
            );
        });
    }
}
