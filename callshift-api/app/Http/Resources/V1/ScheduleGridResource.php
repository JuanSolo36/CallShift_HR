<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class ScheduleGridResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $workPeriod = $this->resource['work_period'];
        $version = $this->resource['version'];
        $employees = $this->resource['employees'];
        $shiftTypes = $this->resource['shift_types'];
        $assignments = $this->resource['assignments'];

        // Construir rango de días
        $startDate = $workPeriod->start_date instanceof Carbon ? $workPeriod->start_date : Carbon::parse($workPeriod->start_date);
        $endDate = $workPeriod->end_date instanceof Carbon ? $workPeriod->end_date : Carbon::parse($workPeriod->end_date);

        $days = [];
        $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

        $period = CarbonPeriod::create($startDate, $endDate);
        foreach ($period as $dt) {
            $days[] = [
                'date'        => $dt->format('Y-m-d'),
                'day_of_week' => $dt->dayOfWeek, // 0 = Dom, 1 = Lun, etc.
                'day_name'    => $dayNames[$dt->dayOfWeek],
                'day_number'  => $dt->day,
                'formatted'   => $dt->format('d/m'),
                'is_weekend'  => $dt->isWeekend(),
            ];
        }

        return [
            'work_period' => [
                'id'            => $workPeriod->id,
                'name'          => $workPeriod->name,
                'department_id' => $workPeriod->department_id,
                'department'    => $workPeriod->department ? [
                    'id'   => $workPeriod->department->id,
                    'name' => $workPeriod->department->name,
                    'code' => $workPeriod->department->code,
                ] : null,
                'start_date'    => $startDate->format('Y-m-d'),
                'end_date'      => $endDate->format('Y-m-d'),
                'duration_days' => count($days),
                'status'        => is_object($workPeriod->status) ? $workPeriod->status->value : $workPeriod->status,
            ],
            'version'     => [
                'id'                   => $version->id,
                'version_number'       => $version->version_number,
                'status'               => is_object($version->status) ? $version->status->value : $version->status,
                'lock_version'         => (int) $version->lock_version,
                'score'                => (float) $version->score,
                'hard_conflicts_count' => (int) $version->hard_conflicts_count,
                'soft_conflicts_count' => (int) $version->soft_conflicts_count,
                'is_editable'          => !$version->isImmutable() && ($workPeriod->status->value ?? $workPeriod->status) !== 'CLOSED',
            ],
            'days'        => $days,
            'employees'   => $employees->map(function ($emp) {
                return [
                    'id'            => $emp->id,
                    'employee_code' => $emp->employee_code,
                    'full_name'     => $emp->first_name . ' ' . $emp->last_name,
                    'first_name'    => $emp->first_name,
                    'last_name'     => $emp->last_name,
                    'department'    => $emp->department ? $emp->department->name : null,
                    'position'      => $emp->position ? $emp->position->name : null,
                ];
            }),
            'shift_types' => $shiftTypes->map(function ($shift) {
                return [
                    'id'               => $shift->id,
                    'name'             => $shift->name,
                    'code'             => $shift->code,
                    'color_hex'        => $shift->color_hex,
                    'start_time'       => substr($shift->start_time, 0, 5),
                    'end_time'         => substr($shift->end_time, 0, 5),
                    'total_work_hours' => (float) $shift->total_work_hours,
                    'crosses_midnight' => (bool) $shift->crosses_midnight,
                ];
            }),
            'assignments' => ScheduleAssignmentResource::collection($assignments),
        ];
    }
}
