<?php

namespace App\Services\Shifts;

use App\Models\ShiftPattern;
use App\Models\ShiftPatternEntry;
use App\Models\ShiftType;
use App\Enums\DayType;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use InvalidArgumentException;

class PatternEngineService
{
    /**
     * Calcula determinísticamente la entrada del patrón para un día dado relativo a la fecha de inicio.
     *
     * @param int $dayOffsetFromStart Índice del día desde la fecha base (0 = día de inicio, 1 = día siguiente, ...)
     * @param int $cycleLength Cantidad de días en el ciclo (ej. 7 para 5x2)
     * @param int $startOffsetDay Día dentro del ciclo asignado a la fecha de inicio (1 .. $cycleLength)
     * @return int day_number correspondiente en las entradas del patrón (1 .. $cycleLength)
     */
    public function calculateCycleDayNumber(int $dayOffsetFromStart, int $cycleLength, int $startOffsetDay = 1): int
    {
        if ($cycleLength < 1) {
            throw new InvalidArgumentException('La longitud del ciclo debe ser mayor o igual a 1.');
        }

        if ($startOffsetDay < 1 || $startOffsetDay > $cycleLength) {
            throw new InvalidArgumentException("El offset inicial ($startOffsetDay) debe estar entre 1 y $cycleLength.");
        }

        // Fórmula: ((startOffsetDay - 1 + dayOffsetFromStart) % cycleLength) + 1
        $zeroBasedDay = ($startOffsetDay - 1 + $dayOffsetFromStart) % $cycleLength;
        if ($zeroBasedDay < 0) {
            $zeroBasedDay += $cycleLength;
        }

        return $zeroBasedDay + 1;
    }

    /**
     * Simula y proyecta las asignaciones diarias para una lista de colaboradores dentro de un rango de fechas.
     *
     * @param ShiftPattern $pattern Patrón con sus entries precargadas
     * @param array $employees Lista de modelos Employee
     * @param string $startDateStr Fecha inicial YYYY-MM-DD
     * @param string $endDateStr Fecha final YYYY-MM-DD
     * @param int $startOffsetDay Día de inicio del ciclo en la fecha $startDateStr
     * @param array $existingAssignmentsMap Mapa existente [employee_id . '_' . date => ScheduleAssignment]
     * @return array
     */
    public function projectAssignments(
        ShiftPattern $pattern,
        array $employees,
        string $startDateStr,
        string $endDateStr,
        int $startOffsetDay = 1,
        array $existingAssignmentsMap = []
    ): array {
        $startDate = Carbon::parse($startDateStr)->startOfDay();
        $endDate   = Carbon::parse($endDateStr)->startOfDay();

        if ($startDate->gt($endDate)) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la fecha final.');
        }

        $cycleLength = (int) $pattern->cycle_length_days;

        // Indexar entries por day_number para acceso O(1)
        $entriesByDay = [];
        foreach ($pattern->entries as $entry) {
            $entriesByDay[(int) $entry->day_number] = $entry;
        }

        $projections       = [];
        $totalWorkHours    = 0.0;
        $totalWorkDays     = 0;
        $totalRestDays     = 0;
        $newCount          = 0;
        $overwrittenCount  = 0;

        $period = CarbonPeriod::create($startDate, $endDate);
        $totalDaysInPeriod = count($period);

        foreach ($employees as $employee) {
            $empProjections = [];
            $dayIndex = 0;

            foreach ($period as $currentDate) {
                $dateStr = $currentDate->format('Y-m-d');
                $cycleDayNumber = $this->calculateCycleDayNumber($dayIndex, $cycleLength, $startOffsetDay);

                $entry = $entriesByDay[$cycleDayNumber] ?? null;
                if (!$entry) {
                    throw new InvalidArgumentException("No se encontró la entrada para el día de ciclo {$cycleDayNumber} en el patrón {$pattern->name}.");
                }

                $dayTypeValue = is_object($entry->day_type) ? $entry->day_type->value : (string) $entry->day_type;
                $shiftType    = $entry->shiftType;

                $startTime  = null;
                $endTime    = null;
                $startsAt   = null;
                $endsAt     = null;
                $totalHours = 0.0;

                if ($dayTypeValue === DayType::WORK->value && $shiftType) {
                    $startTime  = $entry->start_time_override ?? substr($shiftType->start_time, 0, 8);
                    $endTime    = $entry->end_time_override ?? substr($shiftType->end_time, 0, 8);
                    $totalHours = (float) $shiftType->total_work_hours;

                    $startsAt = Carbon::parse("{$dateStr} {$startTime}");
                    if ($shiftType->crosses_midnight) {
                        $endsAt = Carbon::parse("{$dateStr} {$endTime}")->addDay();
                    } else {
                        $endsAt = Carbon::parse("{$dateStr} {$endTime}");
                    }

                    $totalWorkDays++;
                    $totalWorkHours += $totalHours;
                } else {
                    $totalRestDays++;
                }

                $key = "{$employee->id}_{$dateStr}";
                $isOverwriting = isset($existingAssignmentsMap[$key]);
                if ($isOverwriting) {
                    $overwrittenCount++;
                } else {
                    $newCount++;
                }

                $empProjections[] = [
                    'employee_id'     => $employee->id,
                    'employee_name'   => "{$employee->first_name} {$employee->last_name}",
                    'date'            => $dateStr,
                    'day_number'      => $cycleDayNumber,
                    'day_type'        => $dayTypeValue,
                    'shift_type_id'   => $shiftType?->id,
                    'shift_type_name' => $shiftType?->name,
                    'shift_type_code' => $shiftType?->code,
                    'color_hex'       => $shiftType?->color_hex ?? '#9CA3AF',
                    'start_time'      => $startTime ? substr($startTime, 0, 5) : null,
                    'end_time'        => $endTime ? substr($endTime, 0, 5) : null,
                    'starts_at'       => $startsAt?->toIso8601String(),
                    'ends_at'         => $endsAt?->toIso8601String(),
                    'total_hours'     => $totalHours,
                    'is_overwriting'  => $isOverwriting,
                ];

                $dayIndex++;
            }

            $projections[$employee->id] = $empProjections;
        }

        $totalAssignments = count($employees) * $totalDaysInPeriod;

        return [
            'summary' => [
                'employees_count'      => count($employees),
                'total_days_in_period' => $totalDaysInPeriod,
                'total_assignments'    => $totalAssignments,
                'new_assignments'      => $newCount,
                'overwritten_count'    => $overwrittenCount,
                'total_work_hours'     => round($totalWorkHours, 2),
                'total_work_days'      => $totalWorkDays,
                'total_rest_days'      => $totalRestDays,
            ],
            'projections' => $projections,
        ];
    }
}
