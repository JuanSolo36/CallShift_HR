<?php

namespace App\Services\Conflicts;

use App\Enums\AuditAction;
use App\Enums\AbsenceStatus;
use App\Enums\AbsenceType;
use App\Enums\ConflictSeverity;
use App\Enums\ConflictStatus;
use App\Enums\DayType;
use App\Enums\RuleViolated;
use App\Enums\WeekendRotationPolicy;
use App\Models\Absence;
use App\Models\Availability;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleConflict;
use App\Models\ScheduleVersion;
use App\Models\WorkPeriod;
use App\Models\Company;
use App\Models\User;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConflictDetectionService
{
    public function __construct(
        protected BusinessRuleService $ruleService
    ) {}

    /**
     * Genera la clave canónica SHA-256 única para identificar determinísticamente un conflicto.
     */
    public function generateConflictKey(
        int $versionId,
        int $employeeId,
        string $ruleViolated,
        ?string $date,
        ?Carbon $startDatetime,
        ?Carbon $endDatetime,
        array $relatedAssignmentIds = []
    ): string {
        sort($relatedAssignmentIds);
        $canonical = [
            'v'   => $versionId,
            'e'   => $employeeId,
            'r'   => $ruleViolated,
            'd'   => $date ?? '',
            'ts'  => $startDatetime ? $startDatetime->format('Y-m-d H:i') : '',
            'te'  => $endDatetime ? $endDatetime->format('Y-m-d H:i') : '',
            'ids' => array_values($relatedAssignmentIds),
        ];

        return hash('sha256', json_encode($canonical, JSON_UNESCAPED_SLASHES));
    }

    /**
     * Ejecuta el motor de evaluación canónica de las 11 reglas de conflicto sobre una versión de horario.
     */
    public function validateVersion(ScheduleVersion $version, ?User $actor = null): Collection
    {
        $workPeriod = $version->workPeriod ?? WorkPeriod::withoutGlobalScopes()->find($version->work_period_id);
        $company    = $workPeriod?->company ?? Company::withoutGlobalScopes()->find($workPeriod?->company_id);
        $timezone   = $company?->timezone ?? config('app.timezone', 'America/Lima');

        $periodStart = Carbon::parse($workPeriod->start_date, $timezone)->startOfDay();
        $periodEnd   = Carbon::parse($workPeriod->end_date, $timezone)->endOfDay();

        // 1. Cargar colaboradores activos del alcance del periodo
        $empQuery = Employee::with(['employmentType', 'department'])
            ->where('company_id', $workPeriod->company_id)
            ->where('status', 'ACTIVE');

        if ($workPeriod->department_id) {
            $empQuery->where('department_id', $workPeriod->department_id);
        }

        $employees = $empQuery->get();

        // 2. Cargar todas las asignaciones de la versión actual
        $assignments = ScheduleAssignment::with('shiftType')
            ->where('schedule_version_id', $version->id)
            ->orderBy('date')
            ->get();

        $assignmentsByEmployee = $assignments->groupBy('employee_id');

        // 3. Normalizar asignaciones con timestamps en el timezone de la empresa
        $normalizedByEmployee = [];
        foreach ($employees as $emp) {
            $empAssignments = $assignmentsByEmployee->get($emp->id, collect());
            $normalizedList = [];

            foreach ($empAssignments as $ass) {
                $dateStr = $ass->date instanceof \DateTimeInterface ? $ass->date->format('Y-m-d') : substr((string)$ass->date, 0, 10);
                $dayTypeValue = is_object($ass->day_type) ? $ass->day_type->value : (string) $ass->day_type;
                $shift = $ass->shiftType;

                $startDt = null;
                $endDt   = null;
                $workHours = 0.0;

                if ($dayTypeValue === DayType::WORK->value && $shift) {
                    $startTime = $ass->start_time ? substr((string)$ass->start_time, 0, 8) : substr((string)$shift->start_time, 0, 8);
                    $endTime   = $ass->end_time ? substr((string)$ass->end_time, 0, 8) : substr((string)$shift->end_time, 0, 8);
                    $workHours = $ass->total_hours !== null ? (float) $ass->total_hours : (float) $shift->total_work_hours;

                    $startDt = Carbon::parse("{$dateStr} {$startTime}", $timezone);
                    if ($shift->crosses_midnight) {
                        $endDt = Carbon::parse("{$dateStr} {$endTime}", $timezone)->addDay();
                    } else {
                        $endDt = Carbon::parse("{$dateStr} {$endTime}", $timezone);
                    }
                }

                $normalizedList[] = [
                    'id'               => $ass->id,
                    'assignment'       => $ass,
                    'date'             => $dateStr,
                    'day_type'         => $dayTypeValue,
                    'shift_type_id'    => $shift?->id,
                    'shift'            => $shift,
                    'start_datetime'   => $startDt,
                    'end_datetime'     => $endDt,
                    'total_work_hours' => $workHours,
                ];
            }

            // Ordenar por start_datetime / fecha
            usort($normalizedList, function ($a, $b) {
                if ($a['start_datetime'] && $b['start_datetime']) {
                    return $a['start_datetime']->timestamp <=> $b['start_datetime']->timestamp;
                }
                return strcmp($a['date'], $b['date']);
            });

            $normalizedByEmployee[$emp->id] = $normalizedList;
        }

        // 4. Precarga masiva en batch para eliminar N+1 queries
        $empIds = $employees->pluck('id')->all();
        $rulesMap = $this->ruleService->getRulesMapForCompany((int)$workPeriod->company_id);

        $absencesByEmp = Absence::whereIn('employee_id', $empIds)
            ->whereDate('start_date', '<=', $periodEnd->format('Y-m-d'))
            ->whereDate('end_date', '>=', $periodStart->format('Y-m-d'))
            ->get()
            ->groupBy('employee_id');

        $leaveRequestsByEmp = LeaveRequest::whereIn('employee_id', $empIds)
            ->where('status', AbsenceStatus::APPROVED->value)
            ->whereDate('start_date', '<=', $periodEnd->format('Y-m-d'))
            ->whereDate('end_date', '>=', $periodStart->format('Y-m-d'))
            ->get()
            ->groupBy('employee_id');

        $availabilitiesByEmp = Availability::whereIn('employee_id', $empIds)
            ->where('status', 'ACTIVE')
            ->get()
            ->groupBy('employee_id');

        $maxHistoryDays = 14;
        $historyStart = $periodStart->copy()->subDays($maxHistoryDays)->format('Y-m-d');
        $priorAssignmentsByEmp = ScheduleAssignment::whereIn('employee_id', $empIds)
            ->whereBetween('date', [$historyStart, $periodStart->copy()->subDay()->format('Y-m-d')])
            ->whereHas('scheduleVersion', fn($q) => $q->where('status', 'PUBLISHED'))
            ->get()
            ->groupBy('employee_id');

        $detectedConflicts = [];

        foreach ($employees as $employee) {
            $effectiveRule = $this->ruleService->resolveEffectiveRule($employee, $rulesMap);
            $empNormalized = $normalizedByEmployee[$employee->id] ?? [];

            // A. Reglas R1, R2, R4, R5, R10 (Solapamiento, Descanso, Horas Diarias, Nocturno)
            $this->evaluateShiftIntervalRules($version, $employee, $effectiveRule, $empNormalized, $detectedConflicts, $timezone);

            // B. Reglas R6, R7, R8 (Límites Semanales ISO)
            $this->evaluateWeeklyHoursRules($version, $employee, $effectiveRule, $empNormalized, $periodStart, $periodEnd, $detectedConflicts, $timezone);

            // C. Regla R3 (Rachas de Días Consecutivos con Contexto Histórico)
            $empPrior = $priorAssignmentsByEmp->get($employee->id, collect());
            $this->evaluateConsecutiveDaysRule($version, $employee, $effectiveRule, $empNormalized, $periodStart, $periodEnd, $detectedConflicts, $timezone, $empPrior);

            // D. Regla R9 (Colisión con Ausencias / Permisos Aprobados)
            $empAbsences = $absencesByEmp->get($employee->id, collect());
            $empLeaves   = $leaveRequestsByEmp->get($employee->id, collect());
            $this->evaluateAbsenceCollisionRule($version, $employee, $empNormalized, $detectedConflicts, $timezone, $empAbsences, $empLeaves);

            // E. Regla R10 (Restricciones de Disponibilidad)
            $empAvail = $availabilitiesByEmp->get($employee->id, collect());
            $this->evaluateAvailabilityRules($version, $employee, $empNormalized, $detectedConflicts, $timezone, $empAvail);

            // F. Regla R11 (Equidad y Rotación de Fines de Semana)
            $this->evaluateWeekendRotationRule($version, $employee, $effectiveRule, $empNormalized, $periodStart, $periodEnd, $detectedConflicts, $timezone);
        }

        // 5. Persistencia Atómica e Idempotente con Preservación Forense
        return DB::transaction(function () use ($version, $detectedConflicts) {
            $liveKeys = array_keys($detectedConflicts);

            // Marcar como AUTO_CLEARED los conflictos que ya no existen en la malla viva
            ScheduleConflict::where('schedule_version_id', $version->id)
                ->where('status', ConflictStatus::ACTIVE)
                ->whereNotIn('conflict_key', $liveKeys)
                ->update([
                    'status' => ConflictStatus::AUTO_CLEARED,
                ]);

            // Sincronizar o insertar conflictos detectados
            $results = new Collection();

            foreach ($detectedConflicts as $key => $conflictData) {
                $existing = ScheduleConflict::where('schedule_version_id', $version->id)
                    ->where('conflict_key', $key)
                    ->first();

                if ($existing) {
                    // Si ya estaba resuelto, preservar el estado RESOLVED y justificación
                    if ($existing->status === ConflictStatus::RESOLVED || $existing->is_resolved) {
                        $existing->update([
                            'description'               => $conflictData['description'],
                            'suggested_resolution'      => $conflictData['suggested_resolution'] ?? null,
                            'primary_assignment_id'     => $conflictData['primary_assignment_id'] ?? null,
                            'conflicting_assignment_id' => $conflictData['conflicting_assignment_id'] ?? null,
                        ]);
                    } else {
                        $existing->update([
                            'status'                    => ConflictStatus::ACTIVE,
                            'is_resolved'               => false,
                            'description'               => $conflictData['description'],
                            'suggested_resolution'      => $conflictData['suggested_resolution'] ?? null,
                            'primary_assignment_id'     => $conflictData['primary_assignment_id'] ?? null,
                            'conflicting_assignment_id' => $conflictData['conflicting_assignment_id'] ?? null,
                        ]);
                    }
                    $results->push($existing);
                } else {
                    $created = ScheduleConflict::create($conflictData);
                    $results->push($created);
                }
            }

            return $results;
        });
    }

    /**
     * R1: Overlap | R2: Descanso | R4/R5: Horas Diarias | R12: Nocturnos
     */
    protected function evaluateShiftIntervalRules(
        ScheduleVersion $version,
        Employee $employee,
        object $rule,
        array $assignments,
        array &$conflicts,
        string $timezone
    ): void {
        $workShifts = array_values(array_filter($assignments, fn($a) => $a['day_type'] === DayType::WORK->value && $a['start_datetime'] && $a['end_datetime']));
        $count = count($workShifts);

        // 1. R1: Solapamiento y R2: Descanso Mínimo entre turnos correlativos
        for ($i = 0; $i < $count; $i++) {
            $curr = $workShifts[$i];

            // R12: Turno nocturno prohibido
            if (!$rule->allow_night_shifts) {
                $isNight = $curr['shift']?->crosses_midnight || ($curr['start_datetime']->hour >= 22 || $curr['start_datetime']->hour < 6 || $curr['end_datetime']->hour <= 6);
                if ($isNight) {
                    $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::NIGHT_SHIFT_DISALLOWED->value, $curr['date'], $curr['start_datetime'], $curr['end_datetime'], [$curr['id']]);
                    $conflicts[$key] = [
                        'schedule_version_id'   => $version->id,
                        'employee_id'           => $employee->id,
                        'conflict_key'          => $key,
                        'date'                  => $curr['date'],
                        'start_datetime'        => $curr['start_datetime'],
                        'end_datetime'          => $curr['end_datetime'],
                        'severity'              => ConflictSeverity::HARD_CONFLICT,
                        'rule_violated'         => RuleViolated::NIGHT_SHIFT_DISALLOWED->value,
                        'description'           => "El colaborador {$employee->first_name} tiene asignado un turno nocturno ({$curr['shift']?->name}) prohibido por la regla laboral.",
                        'suggested_resolution'  => 'Asignar un turno diurno o habilitar turnos nocturnos en la regla de negocio.',
                        'primary_assignment_id' => $curr['id'],
                        'status'                => ConflictStatus::ACTIVE,
                        'is_resolved'           => false,
                    ];
                }
            }

            for ($j = $i + 1; $j < $count; $j++) {
                $next = $workShifts[$j];

                // R1. Overlapping Shifts
                if ($curr['start_datetime']->lt($next['end_datetime']) && $curr['end_datetime']->gt($next['start_datetime'])) {
                    $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::OVERLAPPING_SHIFTS->value, $curr['date'], $curr['start_datetime'], $next['end_datetime'], [$curr['id'], $next['id']]);
                    $conflicts[$key] = [
                        'schedule_version_id'       => $version->id,
                        'employee_id'               => $employee->id,
                        'conflict_key'              => $key,
                        'date'                      => $curr['date'],
                        'start_datetime'            => $curr['start_datetime'],
                        'end_datetime'              => $next['end_datetime'],
                        'severity'                  => ConflictSeverity::HARD_CONFLICT,
                        'rule_violated'             => RuleViolated::OVERLAPPING_SHIFTS->value,
                        'description'               => "Solapamiento de turnos para {$employee->first_name} entre {$curr['start_datetime']->format('H:i')} y {$next['start_datetime']->format('H:i')}.",
                        'suggested_resolution'      => 'Reasignar o eliminar uno de los turnos solapados.',
                        'primary_assignment_id'     => $curr['id'],
                        'conflicting_assignment_id' => $next['id'],
                        'status'                    => ConflictStatus::ACTIVE,
                        'is_resolved'               => false,
                    ];
                }

                // R2. Descanso mínimo entre turnos consecutivos
                if ($j === $i + 1 && $curr['end_datetime']->lte($next['start_datetime'])) {
                    $restMinutes = $curr['end_datetime']->diffInMinutes($next['start_datetime']);
                    $minRestMinutes = (int) ($rule->min_rest_hours_between_shifts * 60);

                    if ($restMinutes < $minRestMinutes) {
                        $restHoursFormatted = round($restMinutes / 60, 1);
                        $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::MIN_REST_BETWEEN_SHIFTS->value, $curr['date'], $curr['end_datetime'], $next['start_datetime'], [$curr['id'], $next['id']]);
                        $conflicts[$key] = [
                            'schedule_version_id'       => $version->id,
                            'employee_id'               => $employee->id,
                            'conflict_key'              => $key,
                            'date'                      => $curr['date'],
                            'start_datetime'            => $curr['end_datetime'],
                            'end_datetime'              => $next['start_datetime'],
                            'severity'                  => ConflictSeverity::HARD_CONFLICT,
                            'rule_violated'             => RuleViolated::MIN_REST_BETWEEN_SHIFTS->value,
                            'description'               => "Descanso insuficiente de {$restHoursFormatted}h entre turnos (mínimo legal requerido: {$rule->min_rest_hours_between_shifts}h).",
                            'suggested_resolution'      => "Aumentar el intervalo de descanso entre el fin del turno en {$curr['date']} y el inicio del siguiente.",
                            'primary_assignment_id'     => $curr['id'],
                            'conflicting_assignment_id' => $next['id'],
                            'status'                    => ConflictStatus::ACTIVE,
                            'is_resolved'               => false,
                        ];
                    }
                }
            }
        }

        // 2. R4 / R5: Agregación diaria de horas computables
        $byDate = [];
        foreach ($assignments as $a) {
            $d = $a['date'];
            if (!isset($byDate[$d])) {
                $byDate[$d] = ['hours' => 0.0, 'ids' => [], 'has_work' => false];
            }
            if ($a['day_type'] === DayType::WORK->value) {
                $byDate[$d]['hours'] += (float) $a['total_work_hours'];
                $byDate[$d]['ids'][] = $a['id'];
                $byDate[$d]['has_work'] = true;
            }
        }

        foreach ($byDate as $dateStr => $data) {
            if (!$data['has_work']) {
                continue;
            }

            $hours = $data['hours'];

            // R4: Exceso de horas diarias
            if ($hours > $rule->max_daily_hours) {
                $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::MAX_DAILY_HOURS->value, $dateStr, null, null, $data['ids']);
                $conflicts[$key] = [
                    'schedule_version_id'   => $version->id,
                    'employee_id'           => $employee->id,
                    'conflict_key'          => $key,
                    'date'                  => $dateStr,
                    'start_datetime'        => Carbon::parse("{$dateStr} 00:00:00", $timezone),
                    'end_datetime'          => Carbon::parse("{$dateStr} 23:59:59", $timezone),
                    'severity'              => ConflictSeverity::HARD_CONFLICT,
                    'rule_violated'         => RuleViolated::MAX_DAILY_HOURS->value,
                    'description'           => "Jornada diaria de {$hours}h excede el límite máximo legal de {$rule->max_daily_hours}h.",
                    'suggested_resolution'  => 'Reducir las horas de turno asignadas o dividir la jornada.',
                    'primary_assignment_id' => $data['ids'][0] ?? null,
                    'status'                => ConflictStatus::ACTIVE,
                    'is_resolved'           => false,
                ];
            }

            // R5: Déficit de horas mínimas diarias
            if ($hours > 0 && $hours < $rule->min_daily_hours) {
                $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::MIN_DAILY_HOURS->value, $dateStr, null, null, $data['ids']);
                $conflicts[$key] = [
                    'schedule_version_id'   => $version->id,
                    'employee_id'           => $employee->id,
                    'conflict_key'          => $key,
                    'date'                  => $dateStr,
                    'start_datetime'        => Carbon::parse("{$dateStr} 00:00:00", $timezone),
                    'end_datetime'          => Carbon::parse("{$dateStr} 23:59:59", $timezone),
                    'severity'              => ConflictSeverity::SOFT_WARNING,
                    'rule_violated'         => RuleViolated::MIN_DAILY_HOURS->value,
                    'description'           => "Jornada diaria de {$hours}h es inferior al mínimo diario sugerido de {$rule->min_daily_hours}h.",
                    'suggested_resolution'  => 'Asignar un turno completo o consolidar horas.',
                    'primary_assignment_id' => $data['ids'][0] ?? null,
                    'status'                => ConflictStatus::ACTIVE,
                    'is_resolved'           => false,
                ];
            }
        }
    }

    /**
     * R6: Límite Legal Semanal | R7: Límite Contractual Semanal | R8: Mínimo Semanal
     */
    protected function evaluateWeeklyHoursRules(
        ScheduleVersion $version,
        Employee $employee,
        object $rule,
        array $assignments,
        Carbon $periodStart,
        Carbon $periodEnd,
        array &$conflicts,
        string $timezone
    ): void {
        // Agrupar asignaciones por semana ISO (YYYY-Www)
        $byIsoWeek = [];
        foreach ($assignments as $a) {
            $dt = Carbon::parse($a['date'], $timezone);
            $isoKey = $dt->isoFormat('GGGG-[W]WW');
            if (!isset($byIsoWeek[$isoKey])) {
                $byIsoWeek[$isoKey] = [
                    'hours'     => 0.0,
                    'ids'       => [],
                    'min_date'  => $dt->copy()->startOfWeek(Carbon::MONDAY),
                    'max_date'  => $dt->copy()->endOfWeek(Carbon::SUNDAY),
                    'days_seen' => [],
                ];
            }
            $byIsoWeek[$isoKey]['days_seen'][$a['date']] = true;
            if ($a['day_type'] === DayType::WORK->value) {
                $byIsoWeek[$isoKey]['hours'] += (float) $a['total_work_hours'];
                $byIsoWeek[$isoKey]['ids'][] = $a['id'];
            }
        }

        $contractualBase = $employee->employmentType?->weekly_hours_base ? (float) $employee->employmentType->weekly_hours_base : null;

        foreach ($byIsoWeek as $isoKey => $data) {
            $totalHours = $data['hours'];
            $weekStart = $data['min_date'];
            $weekEnd   = $data['max_date'];

            // R6. LEGAL_WEEKLY_HOURS_EXCEEDED (HARD_CONFLICT)
            if ($totalHours > $rule->max_weekly_hours) {
                $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::LEGAL_WEEKLY_HOURS_EXCEEDED->value, $weekStart->format('Y-m-d'), $weekStart, $weekEnd, $data['ids']);
                $conflicts[$key] = [
                    'schedule_version_id'   => $version->id,
                    'employee_id'           => $employee->id,
                    'conflict_key'          => $key,
                    'date'                  => $weekStart->format('Y-m-d'),
                    'start_datetime'        => $weekStart,
                    'end_datetime'          => $weekEnd,
                    'severity'              => ConflictSeverity::HARD_CONFLICT,
                    'rule_violated'         => RuleViolated::LEGAL_WEEKLY_HOURS_EXCEEDED->value,
                    'description'           => "Jornada semanal de {$totalHours}h excede el límite máximo legal de {$rule->max_weekly_hours}h en la semana {$isoKey}.",
                    'suggested_resolution'  => 'Reducir turnos o reasignar colaboradores para respetar el máximo semanal.',
                    'primary_assignment_id' => $data['ids'][0] ?? null,
                    'status'                => ConflictStatus::ACTIVE,
                    'is_resolved'           => false,
                ];
            }
            // R7. CONTRACT_WEEKLY_HOURS_EXCEEDED (SOFT_WARNING)
            elseif ($contractualBase && $totalHours > $contractualBase) {
                $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::CONTRACT_WEEKLY_HOURS_EXCEEDED->value, $weekStart->format('Y-m-d'), $weekStart, $weekEnd, $data['ids']);
                $conflicts[$key] = [
                    'schedule_version_id'   => $version->id,
                    'employee_id'           => $employee->id,
                    'conflict_key'          => $key,
                    'date'                  => $weekStart->format('Y-m-d'),
                    'start_datetime'        => $weekStart,
                    'end_datetime'          => $weekEnd,
                    'severity'              => ConflictSeverity::SOFT_WARNING,
                    'rule_violated'         => RuleViolated::CONTRACT_WEEKLY_HOURS_EXCEEDED->value,
                    'description'           => "Jornada semanal de {$totalHours}h excede la base contractual pactada de {$contractualBase}h ({$employee->employmentType?->name}).",
                    'suggested_resolution'  => 'Verificar compensación de horas extras o balancear la carga.',
                    'primary_assignment_id' => $data['ids'][0] ?? null,
                    'status'                => ConflictStatus::ACTIVE,
                    'is_resolved'           => false,
                ];
            }

            // R8. MIN_WEEKLY_HOURS (SOFT_WARNING) solo para semanas completas cerradas dentro del horizonte
            $isFullWeekInPeriod = $weekStart->format('Y-m-d') >= $periodStart->format('Y-m-d') && $weekEnd->format('Y-m-d') <= $periodEnd->format('Y-m-d');
            if ($isFullWeekInPeriod && $totalHours < $rule->min_weekly_hours) {
                $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::MIN_WEEKLY_HOURS->value, $weekStart->format('Y-m-d'), $weekStart, $weekEnd, $data['ids']);
                $conflicts[$key] = [
                    'schedule_version_id'   => $version->id,
                    'employee_id'           => $employee->id,
                    'conflict_key'          => $key,
                    'date'                  => $weekStart->format('Y-m-d'),
                    'start_datetime'        => $weekStart,
                    'end_datetime'          => $weekEnd,
                    'severity'              => ConflictSeverity::SOFT_WARNING,
                    'rule_violated'         => RuleViolated::MIN_WEEKLY_HOURS->value,
                    'description'           => "Jornada semanal de {$totalHours}h es inferior al mínimo pactado de {$rule->min_weekly_hours}h en la semana {$isoKey}.",
                    'suggested_resolution'  => 'Asignar turnos adicionales para completar la jornada mínima semanal.',
                    'primary_assignment_id' => $data['ids'][0] ?? null,
                    'status'                => ConflictStatus::ACTIVE,
                    'is_resolved'           => false,
                ];
            }
        }
    }

    /**
     * R3: Máximo de Días Consecutivos de Trabajo con Contexto Histórico
     */
    protected function evaluateConsecutiveDaysRule(
        ScheduleVersion $version,
        Employee $employee,
        object $rule,
        array $assignments,
        Carbon $periodStart,
        Carbon $periodEnd,
        array &$conflicts,
        string $timezone,
        $preloadedPrior = null
    ): void {
        $maxConsecutive = (int) $rule->max_consecutive_work_days;
        if ($maxConsecutive < 1) {
            return;
        }

        // Consultar contexto histórico dinámico anterior al inicio del periodo o usar precarga
        $historyStart = $periodStart->copy()->subDays($maxConsecutive);
        $priorAssignments = $preloadedPrior !== null ? $preloadedPrior : ScheduleAssignment::where('employee_id', $employee->id)
            ->whereBetween('date', [$historyStart->format('Y-m-d'), $periodStart->copy()->subDay()->format('Y-m-d')])
            ->whereHas('scheduleVersion', fn($q) => $q->where('status', 'PUBLISHED'))
            ->get();

        $allAssignmentsMap = [];
        foreach ($priorAssignments as $pa) {
            $d = $pa->date instanceof \DateTimeInterface ? $pa->date->format('Y-m-d') : substr((string)$pa->date, 0, 10);
            $allAssignmentsMap[$d] = [
                'day_type' => is_object($pa->day_type) ? $pa->day_type->value : (string) $pa->day_type,
                'hours'    => (float) $pa->total_hours,
                'id'       => $pa->id,
            ];
        }

        foreach ($assignments as $curr) {
            $allAssignmentsMap[$curr['date']] = [
                'day_type' => $curr['day_type'],
                'hours'    => (float) $curr['total_work_hours'],
                'id'       => $curr['id'],
            ];
        }

        // Recorrer cronológicamente los días desde historyStart hasta periodEnd
        $fullPeriod = CarbonPeriod::create($historyStart, $periodEnd);
        $currentStreak = 0;
        $streakIds = [];
        $streakStart = null;

        foreach ($fullPeriod as $day) {
            $dateStr = $day->format('Y-m-d');
            $data = $allAssignmentsMap[$dateStr] ?? null;

            $isWork = $data && $data['day_type'] === DayType::WORK->value && $data['hours'] > 0;

            if ($isWork) {
                if ($currentStreak === 0) {
                    $streakStart = $day->copy();
                }
                $currentStreak++;
                $streakIds[] = $data['id'];

                if ($currentStreak > $maxConsecutive && $day->gte($periodStart)) {
                    $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::MAX_CONSECUTIVE_WORK_DAYS->value, $dateStr, $streakStart, $day, $streakIds);
                    $conflicts[$key] = [
                        'schedule_version_id'   => $version->id,
                        'employee_id'           => $employee->id,
                        'conflict_key'          => $key,
                        'date'                  => $dateStr,
                        'start_datetime'        => $streakStart->copy()->startOfDay(),
                        'end_datetime'          => $day->copy()->endOfDay(),
                        'severity'              => ConflictSeverity::HARD_CONFLICT,
                        'rule_violated'         => RuleViolated::MAX_CONSECUTIVE_WORK_DAYS->value,
                        'description'           => "El colaborador {$employee->first_name} acumula {$currentStreak} días consecutivos de trabajo continuo (máximo permitido: {$maxConsecutive} días).",
                        'suggested_resolution'  => "Asignar un día de descanso (REST / OFF) para romper la racha continua de trabajo en {$dateStr}.",
                        'primary_assignment_id' => $data['id'],
                        'status'                => ConflictStatus::ACTIVE,
                        'is_resolved'           => false,
                    ];
                }
            } else {
                // Racha rota por descanso, ausencia o día sin turno
                $currentStreak = 0;
                $streakIds = [];
                $streakStart = null;
            }
        }
    }

    /**
     * R9: Colisión con Ausencias o Permisos Aprobados
     */
    protected function evaluateAbsenceCollisionRule(
        ScheduleVersion $version,
        Employee $employee,
        array $assignments,
        array &$conflicts,
        string $timezone,
        $preloadedAbsences = null,
        $preloadedLeaves = null
    ): void {
        // Cargar ausencias aprobadas del colaborador (usar precarga en batch)
        $absences = $preloadedAbsences !== null ? $preloadedAbsences : Absence::where('employee_id', $employee->id)
            ->where(function ($q) use ($assignments) {
                if (!empty($assignments)) {
                    $dates = array_column($assignments, 'date');
                    $minDate = min($dates);
                    $maxDate = max($dates);
                    $q->whereDate('start_date', '<=', $maxDate)->whereDate('end_date', '>=', $minDate);
                }
            })
            ->get();

        $approvedRequests = $preloadedLeaves !== null ? $preloadedLeaves : LeaveRequest::where('employee_id', $employee->id)
            ->where('status', AbsenceStatus::APPROVED->value)
            ->where(function ($q) use ($assignments) {
                if (!empty($assignments)) {
                    $dates = array_column($assignments, 'date');
                    $minDate = min($dates);
                    $maxDate = max($dates);
                    $q->whereDate('start_date', '<=', $maxDate)->whereDate('end_date', '>=', $minDate);
                }
            })
            ->get();

        $allAbsenceIntervals = [];
        foreach ($absences as $abs) {
            $sDate = $abs->start_date instanceof \DateTimeInterface ? $abs->start_date->format('Y-m-d') : substr((string)$abs->start_date, 0, 10);
            $eDate = $abs->end_date instanceof \DateTimeInterface ? $abs->end_date->format('Y-m-d') : substr((string)$abs->end_date, 0, 10);
            $typeName = $abs->type instanceof \BackedEnum ? $abs->type->value : (string)$abs->type;

            $allAbsenceIntervals[] = [
                'type'     => $typeName,
                'start_dt' => Carbon::parse("{$sDate} 00:00:00", $timezone),
                'end_dt'   => Carbon::parse("{$eDate} 23:59:59", $timezone),
                'reason'   => $abs->reason ?? 'Ausencia registrada',
            ];
        }
        foreach ($approvedRequests as $req) {
            $sDate = $req->start_date instanceof \DateTimeInterface ? $req->start_date->format('Y-m-d') : substr((string)$req->start_date, 0, 10);
            $eDate = $req->end_date instanceof \DateTimeInterface ? $req->end_date->format('Y-m-d') : substr((string)$req->end_date, 0, 10);
            $typeName = $req->type instanceof \BackedEnum ? $req->type->value : (string)$req->type;

            $allAbsenceIntervals[] = [
                'type'     => $typeName,
                'start_dt' => Carbon::parse("{$sDate} 00:00:00", $timezone),
                'end_dt'   => Carbon::parse("{$eDate} 23:59:59", $timezone),
                'reason'   => $req->reason ?? 'Permiso aprobado',
            ];
        }

        foreach ($assignments as $a) {
            if ($a['day_type'] !== DayType::WORK->value || !$a['start_datetime'] || !$a['end_datetime']) {
                continue;
            }

            foreach ($allAbsenceIntervals as $abs) {
                // Condición de solapamiento de intervalos
                if ($a['start_datetime']->lt($abs['end_dt']) && $a['end_datetime']->gt($abs['start_dt'])) {
                    $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::APPROVED_ABSENCE_COLLISION->value, $a['date'], $a['start_datetime'], $a['end_datetime'], [$a['id']]);
                    $conflicts[$key] = [
                        'schedule_version_id'   => $version->id,
                        'employee_id'           => $employee->id,
                        'conflict_key'          => $key,
                        'date'                  => $a['date'],
                        'start_datetime'        => $a['start_datetime'],
                        'end_datetime'          => $a['end_datetime'],
                        'severity'              => ConflictSeverity::HARD_CONFLICT,
                        'rule_violated'         => RuleViolated::APPROVED_ABSENCE_COLLISION->value,
                        'description'           => "Turno asignado en fecha de ausencia aprobada ({$abs['type']}) para {$employee->first_name}.",
                        'suggested_resolution'  => 'Liberar el turno o marcar el día como descanso/ausencia.',
                        'primary_assignment_id' => $a['id'],
                        'status'                => ConflictStatus::ACTIVE,
                        'is_resolved'           => false,
                    ];
                }
            }
        }
    }

    /**
     * R10: Restricciones de Disponibilidad del Colaborador
     */
    protected function evaluateAvailabilityRules(
        ScheduleVersion $version,
        Employee $employee,
        array $assignments,
        array &$conflicts,
        string $timezone,
        $preloadedAvailabilities = null
    ): void {
        $availabilities = $preloadedAvailabilities !== null ? $preloadedAvailabilities : Availability::where('employee_id', $employee->id)
            ->where('status', 'ACTIVE')
            ->get();

        if ($availabilities->isEmpty()) {
            return;
        }

        foreach ($assignments as $a) {
            if ($a['day_type'] !== DayType::WORK->value || !$a['start_datetime'] || !$a['end_datetime']) {
                continue;
            }

            $date = Carbon::parse($a['date'], $timezone);
            $dayOfWeek = (int) $date->isoFormat('E'); // 1=Lunes ... 7=Domingo

            foreach ($availabilities as $avail) {
                $applies = false;
                if ($avail->type === 'SPECIFIC_DATE' && $avail->specific_date) {
                    $applies = ($avail->specific_date->format('Y-m-d') === $a['date']);
                } elseif ($avail->type === 'RECURRING') {
                    $applies = ((int) $avail->day_of_week === $dayOfWeek);
                }

                if (!$applies) {
                    continue;
                }

                // Si está marcado como NO DISPONIBLE
                if (!$avail->is_available) {
                    $severity = ($avail->priority === 'STRICT_RESTRICTION')
                        ? ConflictSeverity::HARD_CONFLICT
                        : ConflictSeverity::SOFT_WARNING;

                    $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::UNAVAILABLE_RESTRICTION->value, $a['date'], $a['start_datetime'], $a['end_datetime'], [$a['id']]);
                    $conflicts[$key] = [
                        'schedule_version_id'   => $version->id,
                        'employee_id'           => $employee->id,
                        'conflict_key'          => $key,
                        'date'                  => $a['date'],
                        'start_datetime'        => $a['start_datetime'],
                        'end_datetime'          => $a['end_datetime'],
                        'severity'              => $severity,
                        'rule_violated'         => RuleViolated::UNAVAILABLE_RESTRICTION->value,
                        'description'           => "El colaborador {$employee->first_name} tiene restricción de no disponibilidad ({$avail->priority}) en {$a['date']}.",
                        'suggested_resolution'  => 'Respetar la restricción de disponibilidad o coordinar una excepción.',
                        'primary_assignment_id' => $a['id'],
                        'status'                => ConflictStatus::ACTIVE,
                        'is_resolved'           => false,
                    ];
                }
            }
        }
    }

    /**
     * R11: Rotación y Equidad en Fines de Semana (STRICT_ROTATION -> HARD_CONFLICT, FAIR_SHARE -> SOFT_WARNING)
     */
    protected function evaluateWeekendRotationRule(
        ScheduleVersion $version,
        Employee $employee,
        object $rule,
        array $assignments,
        Carbon $periodStart,
        Carbon $periodEnd,
        array &$conflicts,
        string $timezone
    ): void {
        $policy = is_object($rule->weekend_rotation_policy) ? $rule->weekend_rotation_policy->value : (string) $rule->weekend_rotation_policy;
        if ($policy === WeekendRotationPolicy::NONE->value || empty($policy)) {
            return;
        }

        $severity = ($policy === WeekendRotationPolicy::STRICT_ROTATION->value)
            ? ConflictSeverity::HARD_CONFLICT
            : ConflictSeverity::SOFT_WARNING;

        // Agrupar asignaciones por fin de semana (Sábado + Domingo de cada semana ISO)
        $weekendMap = [];
        foreach ($assignments as $a) {
            $dt = Carbon::parse($a['date'], $timezone);
            $dayOfWeek = (int) $dt->isoFormat('E'); // 6=Sábado, 7=Domingo

            if ($dayOfWeek === 6 || $dayOfWeek === 7) {
                $weekKey = $dt->isoFormat('GGGG-[W]WW');
                if (!isset($weekendMap[$weekKey])) {
                    $weekendMap[$weekKey] = ['sat' => false, 'sun' => false, 'ids' => [], 'sat_date' => null];
                }
                if ($a['day_type'] === DayType::WORK->value) {
                    if ($dayOfWeek === 6) {
                        $weekendMap[$weekKey]['sat'] = true;
                        $weekendMap[$weekKey]['sat_date'] = $dt->copy();
                    }
                    if ($dayOfWeek === 7) {
                        $weekendMap[$weekKey]['sun'] = true;
                    }
                    $weekendMap[$weekKey]['ids'][] = $a['id'];
                }
            }
        }

        $sortedWeeks = array_keys($weekendMap);
        sort($sortedWeeks);

        for ($k = 0; $k < count($sortedWeeks) - 1; $k++) {
            $w1 = $sortedWeeks[$k];
            $w2 = $sortedWeeks[$k + 1];

            $workedBoth1 = $weekendMap[$w1]['sat'] && $weekendMap[$w1]['sun'];
            $workedBoth2 = $weekendMap[$w2]['sat'] && $weekendMap[$w2]['sun'];

            if ($workedBoth1 && $workedBoth2) {
                $satDate = $weekendMap[$w2]['sat_date'] ? $weekendMap[$w2]['sat_date']->format('Y-m-d') : null;
                $key = $this->generateConflictKey($version->id, $employee->id, RuleViolated::WEEKEND_ROTATION_VIOLATION->value, $satDate, null, null, $weekendMap[$w2]['ids']);
                $conflicts[$key] = [
                    'schedule_version_id'   => $version->id,
                    'employee_id'           => $employee->id,
                    'conflict_key'          => $key,
                    'date'                  => $satDate,
                    'start_datetime'        => null,
                    'end_datetime'          => null,
                    'severity'              => $severity,
                    'rule_violated'         => RuleViolated::WEEKEND_ROTATION_VIOLATION->value,
                    'description'           => ($severity === ConflictSeverity::HARD_CONFLICT)
                        ? "Infracción estricta de rotación de fines de semana: {$employee->first_name} trabaja dos fines de semana consecutivos ({$w1} y {$w2})."
                        : "Aviso de distribución de fines de semana: {$employee->first_name} trabaja dos fines de semana consecutivos ({$w1} y {$w2}).",
                    'suggested_resolution'  => 'Asignar descanso en uno de los fines de semana para rotación equitativa.',
                    'primary_assignment_id' => $weekendMap[$w2]['ids'][0] ?? null,
                    'status'                => ConflictStatus::ACTIVE,
                    'is_resolved'           => false,
                ];
            }
        }
    }

    /**
     * Resuelve y justifica un conflicto con trazabilidad y auditoría forense.
     */
    public function resolve(ScheduleConflict $conflict, string $reason, User $actor): ScheduleConflict
    {
        $version = $conflict->version;
        if ($version->workPeriod->company_id !== $actor->company_id) {
            throw ValidationException::withMessages([
                'conflict' => 'Acceso denegado: El conflicto pertenece a otra empresa.',
            ]);
        }

        $oldValues = $conflict->toArray();

        $conflict->update([
            'status'            => ConflictStatus::RESOLVED,
            'is_resolved'       => true,
            'resolved_by'       => $actor->id,
            'resolved_at'       => now(),
            'resolution_reason' => trim($reason),
        ]);

        AuditService::log(
            AuditAction::UPDATE,
            ScheduleConflict::class,
            $conflict->id,
            "Conflicto de horario ({$conflict->rule_violated}) justificado/resuelto",
            $oldValues,
            $conflict->fresh()->toArray(),
            $actor->company_id
        );

        return $conflict->fresh(['resolver', 'employee']);
    }
}
