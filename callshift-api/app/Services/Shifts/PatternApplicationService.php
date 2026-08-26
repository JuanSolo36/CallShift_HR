<?php

namespace App\Services\Shifts;

use App\Models\User;
use App\Models\ShiftPattern;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use App\Models\Employee;
use App\Enums\DayType;
use App\Enums\WorkPeriodStatus;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PatternApplicationService
{
    public function __construct(
        protected PatternEngineService $engineService,
        protected AuditService $auditService
    ) {}

    /**
     * Valida la seguridad multi-tenant e inmutabilidad antes de cualquier cálculo o persistencia.
     */
    public function validateContext(
        ScheduleVersion $version,
        ShiftPattern $pattern,
        array $employeeIds,
        User $actor,
        ?string $startDateStr = null,
        ?string $endDateStr = null
    ): array {
        $workPeriod = $version->workPeriod;

        // 1. Aislamiento Multi-Tenant
        if ($workPeriod->company_id !== $actor->company_id) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'Acceso denegado: El periodo laboral pertenece a otra empresa.',
            ], Response::HTTP_FORBIDDEN));
        }

        if ($pattern->company_id !== $actor->company_id) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'Acceso denegado: El patrón de turno pertenece a otra empresa.',
            ], Response::HTTP_FORBIDDEN));
        }

        // 2. Inmutabilidad
        if ($version->isImmutable()) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => "No se pueden aplicar patrones en una versión en estado {$version->status->value}.",
            ], Response::HTTP_FORBIDDEN));
        }

        if ($workPeriod->status === WorkPeriodStatus::CLOSED) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'No se pueden aplicar patrones en un periodo laboral cerrado.',
            ], Response::HTTP_FORBIDDEN));
        }

        // 3. Validar Empleados
        if (empty($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Debe seleccionar al menos un colaborador.',
            ]);
        }

        $employees = Employee::where('company_id', $actor->company_id)
            ->whereIn('id', $employeeIds)
            ->get();

        if ($employees->count() !== count($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => 'Uno o más colaboradores no existen o pertenecen a otra empresa.',
            ]);
        }

        // Si el periodo está acotado por departamento, validar compatibilidad
        if ($workPeriod->department_id) {
            foreach ($employees as $emp) {
                if ($emp->department_id !== $workPeriod->department_id) {
                    throw ValidationException::withMessages([
                        'employee_ids' => "El colaborador {$emp->first_name} {$emp->last_name} no pertenece al departamento del periodo.",
                    ]);
                }
            }
        }

        // 4. Validar Rango de Fechas
        $periodStart = $workPeriod->start_date->format('Y-m-d');
        $periodEnd   = $workPeriod->end_date->format('Y-m-d');

        $effectiveStart = $startDateStr ?? $periodStart;
        $effectiveEnd   = $endDateStr ?? $periodEnd;

        if ($effectiveStart < $periodStart || $effectiveStart > $periodEnd) {
            throw ValidationException::withMessages([
                'start_date' => "La fecha inicial ({$effectiveStart}) está fuera de los límites del periodo ({$periodStart} al {$periodEnd}).",
            ]);
        }

        if ($effectiveEnd < $periodStart || $effectiveEnd > $periodEnd) {
            throw ValidationException::withMessages([
                'end_date' => "La fecha final ({$effectiveEnd}) está fuera de los límites del periodo ({$periodStart} al {$periodEnd}).",
            ]);
        }

        if ($effectiveStart > $effectiveEnd) {
            throw ValidationException::withMessages([
                'start_date' => 'La fecha inicial no puede ser posterior a la fecha final.',
            ]);
        }

        return [
            'work_period'     => $workPeriod,
            'version'         => $version,
            'pattern'         => $pattern->load('entries.shiftType'),
            'employees'       => $employees->all(),
            'effective_start' => $effectiveStart,
            'effective_end'   => $effectiveEnd,
        ];
    }

    /**
     * Previsualiza en memoria la aplicación de un patrón sin alterar la base de datos (Dry-Run).
     */
    public function preview(ScheduleVersion $version, array $data, User $actor): array
    {
        $patternId   = (int) $data['pattern_id'];
        $employeeIds = $data['employee_ids'] ?? [];
        $startOffset = (int) ($data['start_offset_day'] ?? 1);

        $pattern = ShiftPattern::where('company_id', $actor->company_id)->findOrFail($patternId);

        $context = $this->validateContext(
            $version,
            $pattern,
            $employeeIds,
            $actor,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        );

        // Cargar mapa de asignaciones existentes
        $existing = ScheduleAssignment::where('schedule_version_id', $version->id)
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $existingMap = [];
        foreach ($existing as $item) {
            $dateStr = $item->date instanceof \DateTimeInterface ? $item->date->format('Y-m-d') : substr((string)$item->date, 0, 10);
            $existingMap["{$item->employee_id}_{$dateStr}"] = $item;
        }

        $projectionResult = $this->engineService->projectAssignments(
            $context['pattern'],
            $context['employees'],
            $context['effective_start'],
            $context['effective_end'],
            $startOffset,
            $existingMap
        );

        return [
            'pattern'     => $context['pattern'],
            'version'     => $version,
            'work_period' => $context['work_period'],
            'summary'     => $projectionResult['summary'],
            'conflicts'   => [],
            'projections' => $projectionResult['projections'],
        ];
    }

    /**
     * Aplica masiva y transaccionalmente un patrón a una versión de horario con verificación de concurrencia.
     */
    public function apply(ScheduleVersion $version, array $data, User $actor): array
    {
        $patternId        = (int) $data['pattern_id'];
        $employeeIds      = $data['employee_ids'] ?? [];
        $startOffset      = (int) ($data['start_offset_day'] ?? 1);
        $incomingLock     = (int) $data['lock_version'];
        $overrideExisting = isset($data['override_existing']) ? (bool) $data['override_existing'] : true;

        $pattern = ShiftPattern::where('company_id', $actor->company_id)->findOrFail($patternId);

        $context = $this->validateContext(
            $version,
            $pattern,
            $employeeIds,
            $actor,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null
        );

        // Cargar mapa de asignaciones existentes
        $existing = ScheduleAssignment::where('schedule_version_id', $version->id)
            ->whereIn('employee_id', $employeeIds)
            ->get();

        $existingMap = [];
        foreach ($existing as $item) {
            $dateStr = $item->date instanceof \DateTimeInterface ? $item->date->format('Y-m-d') : substr((string)$item->date, 0, 10);
            $existingMap["{$item->employee_id}_{$dateStr}"] = $item;
        }

        // Proyectar asignaciones
        $projectionResult = $this->engineService->projectAssignments(
            $context['pattern'],
            $context['employees'],
            $context['effective_start'],
            $context['effective_end'],
            $startOffset,
            $existingMap
        );

        return DB::transaction(function () use (
            $version,
            $context,
            $projectionResult,
            $incomingLock,
            $overrideExisting,
            $existingMap,
            $startOffset,
            $employeeIds,
            $actor
        ) {
            // Control de concurrencia optimista atómico
            $currentLock = (int) $version->lock_version;
            if ($incomingLock !== $currentLock) {
                throw new HttpResponseException(response()->json([
                    'status'               => 'error',
                    'message'              => "Conflicto de concurrencia: La versión de horario fue modificada por otro usuario. (Versión esperada: {$currentLock}, enviada: {$incomingLock})",
                    'current_lock_version' => $currentLock,
                ], Response::HTTP_CONFLICT));
            }

            $affected = ScheduleVersion::where('id', $version->id)
                ->where('lock_version', $incomingLock)
                ->update(['lock_version' => $incomingLock + 1]);

            if ($affected === 0) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia al aplicar el patrón sobre el horario.',
                ], Response::HTTP_CONFLICT));
            }

            // Persistir cada asignación proyectada respetando override_existing
            $persistedCount = 0;
            $overwrittenApplied = 0;
            $newApplied = 0;

            foreach ($projectionResult['projections'] as $empId => $dayList) {
                foreach ($dayList as $item) {
                    $key = "{$item['employee_id']}_{$item['date']}";
                    $alreadyExists = isset($existingMap[$key]);

                    if ($alreadyExists && !$overrideExisting) {
                        // Respetar asignación existente
                        continue;
                    }

                    ScheduleAssignment::updateOrCreate(
                        [
                            'schedule_version_id' => $version->id,
                            'employee_id'         => $item['employee_id'],
                            'date'                => $item['date'],
                        ],
                        [
                            'day_type'      => $item['day_type'],
                            'shift_type_id' => $item['shift_type_id'],
                            'start_time'    => $item['start_time'] ? "{$item['start_time']}:00" : null,
                            'end_time'      => $item['end_time'] ? "{$item['end_time']}:00" : null,
                            'starts_at'     => $item['starts_at'],
                            'ends_at'       => $item['ends_at'],
                            'total_hours'   => $item['total_hours'],
                            'is_custom'     => false,
                            'notes'         => "Generado por patrón: {$context['pattern']->name}",
                        ]
                    );
                    $persistedCount++;
                    if ($alreadyExists) {
                        $overwrittenApplied++;
                    } else {
                        $newApplied++;
                    }
                }
            }

            // Auditoría forense completa
            AuditService::log(
                \App\Enums\AuditAction::UPDATE,
                ScheduleVersion::class,
                $version->id,
                "Patrón '{$context['pattern']->name}' aplicado masivamente",
                ['lock_version' => $incomingLock],
                [
                    'lock_version'        => $incomingLock + 1,
                    'pattern_id'          => $context['pattern']->id,
                    'pattern_code'        => $context['pattern']->code,
                    'work_period_id'      => $context['work_period']->id,
                    'start_date'          => $context['effective_start'],
                    'end_date'            => $context['effective_end'],
                    'start_offset_day'    => $startOffset,
                    'override_existing'   => $overrideExisting,
                    'applied_employees'   => count($context['employees']),
                    'employee_ids'        => $employeeIds,
                    'persisted_count'     => $persistedCount,
                    'new_assignments'     => $newApplied,
                    'overwritten_count'   => $overwrittenApplied,
                ],
                $actor->company_id
            );

            return [
                'success'           => true,
                'message'           => "Patrón '{$context['pattern']->name}' aplicado exitosamente a " . count($context['employees']) . " colaborador(es).",
                'lock_version'      => $incomingLock + 1,
                'summary'           => $projectionResult['summary'],
                'persisted_count'   => $persistedCount,
            ];
        });
    }
}
