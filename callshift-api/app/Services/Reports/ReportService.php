<?php

namespace App\Services\Reports;

use App\Models\Employee;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleVersion;
use App\Models\ScheduleModification;
use App\Models\Absence;
use App\Models\AuditLog;
use App\Models\User;
use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Enums\DayType;
use App\Services\Audit\AuditService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    // =========================================================================
    // 1. REPORTE DE EMPLEADOS
    // =========================================================================

    public function getEmployeesReport(array $filters, User $actor, int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildEmployeesQuery($filters, $actor)->paginate(max(1, min($perPage, 100)));
    }

    public function exportEmployeesCsv(array $filters, User $actor): string
    {
        $employees = $this->buildEmployeesQuery($filters, $actor)->limit(5000)->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'Codigo',
            'Nombre Completo',
            'Tipo Doc',
            'Numero Doc',
            'Email',
            'Departamento',
            'Puesto',
            'Tipo Empleo',
            'Supervisor',
            'Fecha Ingreso',
            'Estado',
        ]);

        foreach ($employees as $emp) {
            fputcsv($output, [
                $emp->employee_code,
                $emp->full_name,
                $emp->document_type,
                $emp->document_number,
                $emp->email,
                $emp->department?->name ?? 'N/A',
                $emp->position?->name ?? 'N/A',
                $emp->employmentType?->name ?? 'N/A',
                $emp->supervisor?->full_name ?? 'N/A',
                $emp->hire_date ? $emp->hire_date->format('Y-m-d') : '',
                is_object($emp->status) ? $emp->status->value : (string)$emp->status,
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        AuditService::logExport(Employee::class, "Exportación de Reporte de Empleados por '{$actor->username}'", $filters, $actor->company_id, $actor);

        return $csv ?: '';
    }

    protected function buildEmployeesQuery(array $filters, User $actor): Builder
    {
        return Employee::query()
            ->where('company_id', $actor->company_id)
            ->with(['department:id,name,code', 'position:id,name,code', 'employmentType:id,name,code', 'supervisor:id,first_name,last_name,employee_code'])
            ->when(!empty($filters['department_id']), fn(Builder $q) => $q->where('department_id', (int)$filters['department_id']))
            ->when(!empty($filters['position_id']), fn(Builder $q) => $q->where('position_id', (int)$filters['position_id']))
            ->when(!empty($filters['employment_type_id']), fn(Builder $q) => $q->where('employment_type_id', (int)$filters['employment_type_id']))
            ->when(!empty($filters['status']), function (Builder $q) use ($filters) {
                $statusVal = $filters['status'] instanceof EmployeeStatus ? $filters['status']->value : (string)$filters['status'];
                $q->where('status', $statusVal);
            })
            ->when(!empty($filters['hire_date_from']), fn(Builder $q) => $q->where('hire_date', '>=', $filters['hire_date_from']))
            ->when(!empty($filters['hire_date_to']), fn(Builder $q) => $q->where('hire_date', '<=', $filters['hire_date_to']))
            ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                $t = "%{$filters['search']}%";
                $q->where(function (Builder $sub) use ($t) {
                    $sub->where('first_name', 'like', $t)
                        ->orWhere('last_name', 'like', $t)
                        ->orWhere('employee_code', 'like', $t)
                        ->orWhere('document_number', 'like', $t)
                        ->orWhere('email', 'like', $t);
                });
            })
            ->orderBy('last_name', 'asc')
            ->orderBy('first_name', 'asc');
    }

    // =========================================================================
    // 2. REPORTE DE HORARIOS
    // =========================================================================

    public function getSchedulesReport(array $filters, User $actor, int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildSchedulesQuery($filters, $actor)->paginate(max(1, min($perPage, 100)));
    }

    public function exportSchedulesCsv(array $filters, User $actor): string
    {
        $assignments = $this->buildSchedulesQuery($filters, $actor)->limit(5000)->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'Empleado',
            'Codigo',
            'Departamento',
            'Periodo',
            'Version',
            'Fecha',
            'Tipo Dia',
            'Turno',
            'Hora Inicio',
            'Hora Fin',
            'Total Horas',
            'Personalizado',
            'Notas',
        ]);

        foreach ($assignments as $a) {
            fputcsv($output, [
                $a->employee?->full_name ?? 'N/A',
                $a->employee?->employee_code ?? 'N/A',
                $a->employee?->department?->name ?? 'N/A',
                $a->scheduleVersion?->workPeriod?->name ?? 'N/A',
                $a->scheduleVersion ? "V{$a->scheduleVersion->version_number}" : 'N/A',
                $a->date ? (is_string($a->date) ? $a->date : $a->date->format('Y-m-d')) : '',
                is_object($a->day_type) ? $a->day_type->value : (string)$a->day_type,
                $a->shiftType?->name ?? 'N/A',
                $a->start_time ?? '',
                $a->end_time ?? '',
                $a->total_hours,
                $a->is_custom ? 'SI' : 'NO',
                $a->notes ?? '',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        AuditService::logExport(ScheduleAssignment::class, "Exportación de Reporte de Horarios por '{$actor->username}'", $filters, $actor->company_id, $actor);

        return $csv ?: '';
    }

    protected function buildSchedulesQuery(array $filters, User $actor): Builder
    {
        return ScheduleAssignment::query()
            ->whereHas('scheduleVersion.workPeriod', function (Builder $q) use ($actor) {
                $q->where('company_id', $actor->company_id);
            })
            ->with([
                'employee:id,first_name,last_name,employee_code,department_id',
                'employee.department:id,name',
                'shiftType:id,name,code,color',
                'scheduleVersion:id,version_number,status,work_period_id',
                'scheduleVersion.workPeriod:id,name,start_date,end_date',
            ])
            ->when(!empty($filters['schedule_version_id']), fn(Builder $q) => $q->where('schedule_version_id', (int)$filters['schedule_version_id']))
            ->when(!empty($filters['work_period_id']), function (Builder $q) use ($filters) {
                $q->whereHas('scheduleVersion', fn(Builder $sq) => $sq->where('work_period_id', (int)$filters['work_period_id']));
            })
            ->when(!empty($filters['employee_id']), fn(Builder $q) => $q->where('employee_id', (int)$filters['employee_id']))
            ->when(!empty($filters['department_id']), function (Builder $q) use ($filters) {
                $q->whereHas('employee', fn(Builder $eq) => $eq->where('department_id', (int)$filters['department_id']));
            })
            ->when(!empty($filters['day_type']), fn(Builder $q) => $q->where('day_type', (string)$filters['day_type']))
            ->when(!empty($filters['date_from']), fn(Builder $q) => $q->where('date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn(Builder $q) => $q->where('date', '<=', $filters['date_to']))
            ->orderBy('date', 'asc')
            ->orderBy('employee_id', 'asc');
    }

    // =========================================================================
    // 3. REPORTE DE HORAS TRABAJADAS
    // =========================================================================

    public function getHoursReport(array $filters, User $actor): array
    {
        $query = $this->buildSchedulesQuery($filters, $actor);

        $assignments = $query->get();

        $byEmployee = $assignments->groupBy('employee_id')->map(function (Collection $items) {
            $first = $items->first();
            $emp = $first->employee;

            $workHours = $items->where('day_type', DayType::WORK->value)->sum('total_hours');
            $workDays = $items->where('day_type', DayType::WORK->value)->count();
            $restDays = $items->where('day_type', DayType::REST->value)->count();
            $absenceDays = $items->whereIn('day_type', [DayType::ABSENCE->value, DayType::PERMISSION->value])->count();
            $avgDaily = $workDays > 0 ? round($workHours / $workDays, 2) : 0;

            return [
                'employee_id'        => $emp?->id,
                'employee_code'      => $emp?->employee_code,
                'employee_name'      => $emp?->full_name,
                'department'         => $emp?->department?->name ?? 'N/A',
                'total_work_hours'   => round($workHours, 2),
                'total_work_days'    => $workDays,
                'total_rest_days'    => $restDays,
                'total_absence_days' => $absenceDays,
                'average_hours_day'  => $avgDaily,
            ];
        })->values();

        $totalHoursAll = $byEmployee->sum('total_work_hours');
        $totalEmployees = $byEmployee->count();
        $avgHoursAll = $totalEmployees > 0 ? round($totalHoursAll / $totalEmployees, 2) : 0;

        return [
            'summary' => [
                'total_employees'   => $totalEmployees,
                'total_hours'       => round($totalHoursAll, 2),
                'average_per_staff' => $avgHoursAll,
            ],
            'employees' => $byEmployee,
        ];
    }

    public function exportHoursCsv(array $filters, User $actor): string
    {
        $reportData = $this->getHoursReport($filters, $actor);

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'Codigo Empleado',
            'Nombre Empleado',
            'Departamento',
            'Total Horas Trabajadas',
            'Dias Laborados',
            'Dias Descanso',
            'Dias Ausencia',
            'Promedio Horas/Dia',
        ]);

        foreach ($reportData['employees'] as $row) {
            fputcsv($output, [
                $row['employee_code'],
                $row['employee_name'],
                $row['department'],
                $row['total_work_hours'],
                $row['total_work_days'],
                $row['total_rest_days'],
                $row['total_absence_days'],
                $row['average_hours_day'],
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        AuditService::logExport(ScheduleAssignment::class, "Exportación de Reporte de Horas por '{$actor->username}'", $filters, $actor->company_id, $actor);

        return $csv ?: '';
    }

    // =========================================================================
    // 4. REPORTE DE AUSENCIAS
    // =========================================================================

    public function getAbsencesReport(array $filters, User $actor, int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildAbsencesQuery($filters, $actor)->paginate(max(1, min($perPage, 100)));
    }

    public function exportAbsencesCsv(array $filters, User $actor): string
    {
        $absences = $this->buildAbsencesQuery($filters, $actor)->limit(5000)->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'Empleado',
            'Codigo',
            'Departamento',
            'Tipo Ausencia',
            'Fecha Inicio',
            'Fecha Fin',
            'Dia Completo',
            'Hora Inicio',
            'Hora Fin',
            'Estado',
            'Motivo',
            'Aprobado Por',
        ]);

        foreach ($absences as $abs) {
            fputcsv($output, [
                $abs->employee?->full_name ?? 'N/A',
                $abs->employee?->employee_code ?? 'N/A',
                $abs->employee?->department?->name ?? 'N/A',
                is_object($abs->type) ? $abs->type->value : (string)$abs->type,
                $abs->start_date ? $abs->start_date->format('Y-m-d') : '',
                $abs->end_date ? $abs->end_date->format('Y-m-d') : '',
                $abs->is_full_day ? 'SI' : 'NO',
                $abs->start_time ?? '',
                $abs->end_time ?? '',
                is_object($abs->status) ? $abs->status->value : (string)$abs->status,
                $abs->reason ?? '',
                $abs->approver?->username ?? 'N/A',
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        AuditService::logExport(Absence::class, "Exportación de Reporte de Ausencias por '{$actor->username}'", $filters, $actor->company_id, $actor);

        return $csv ?: '';
    }

    protected function buildAbsencesQuery(array $filters, User $actor): Builder
    {
        return Absence::query()
            ->where('company_id', $actor->company_id)
            ->with([
                'employee:id,first_name,last_name,employee_code,department_id',
                'employee.department:id,name',
                'approver:id,username,email',
            ])
            ->when(!empty($filters['employee_id']), fn(Builder $q) => $q->where('employee_id', (int)$filters['employee_id']))
            ->when(!empty($filters['department_id']), function (Builder $q) use ($filters) {
                $q->whereHas('employee', fn(Builder $eq) => $eq->where('department_id', (int)$filters['department_id']));
            })
            ->when(!empty($filters['type']), fn(Builder $q) => $q->where('type', (string)$filters['type']))
            ->when(!empty($filters['status']), fn(Builder $q) => $q->where('status', (string)$filters['status']))
            ->when(!empty($filters['date_from']), fn(Builder $q) => $q->where('start_date', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn(Builder $q) => $q->where('end_date', '<=', $filters['date_to']))
            ->orderBy('start_date', 'desc');
    }

    // =========================================================================
    // 5. REPORTE DE MODIFICACIONES (FASE 17)
    // =========================================================================

    public function getModificationsReport(array $filters, User $actor, int $perPage = 25): LengthAwarePaginator
    {
        return $this->buildModificationsQuery($filters, $actor)->paginate(max(1, min($perPage, 100)));
    }

    public function exportModificationsCsv(array $filters, User $actor): string
    {
        $mods = $this->buildModificationsQuery($filters, $actor)->limit(5000)->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, [
            'ID',
            'Fecha Modificacion',
            'Empleado',
            'Codigo',
            'Departamento',
            'Version',
            'Tipo Modificacion',
            'Motivo',
            'Usuario Responsable',
            'Evidencias Adjuntas',
        ]);

        foreach ($mods as $m) {
            fputcsv($output, [
                $m->id,
                $m->created_at ? $m->created_at->format('Y-m-d H:i:s') : '',
                $m->employee?->full_name ?? 'N/A',
                $m->employee?->employee_code ?? 'N/A',
                $m->employee?->department?->name ?? 'N/A',
                $m->version ? "V{$m->version->version_number}" : 'N/A',
                is_object($m->modification_type) ? $m->modification_type->value : (string)$m->modification_type,
                $m->reason,
                $m->creator?->username ?? 'N/A',
                $m->evidences_count ?? ($m->evidences ? $m->evidences->count() : 0),
            ]);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        AuditService::logExport(ScheduleModification::class, "Exportación de Reporte de Modificaciones por '{$actor->username}'", $filters, $actor->company_id, $actor);

        return $csv ?: '';
    }

    protected function buildModificationsQuery(array $filters, User $actor): Builder
    {
        return ScheduleModification::query()
            ->whereHas('version.workPeriod', function (Builder $q) use ($actor) {
                $q->where('company_id', $actor->company_id);
            })
            ->with([
                'employee:id,first_name,last_name,employee_code,department_id',
                'employee.department:id,name',
                'version:id,version_number,work_period_id',
                'creator:id,username,email',
                'evidences',
            ])
            ->withCount('evidences')
            ->when(!empty($filters['schedule_version_id']), fn(Builder $q) => $q->where('schedule_version_id', (int)$filters['schedule_version_id']))
            ->when(!empty($filters['work_period_id']), function (Builder $q) use ($filters) {
                $q->whereHas('version', fn(Builder $vq) => $vq->where('work_period_id', (int)$filters['work_period_id']));
            })
            ->when(!empty($filters['employee_id']), fn(Builder $q) => $q->where('employee_id', (int)$filters['employee_id']))
            ->when(!empty($filters['department_id']), function (Builder $q) use ($filters) {
                $q->whereHas('employee', fn(Builder $eq) => $eq->where('department_id', (int)$filters['department_id']));
            })
            ->when(!empty($filters['modification_type']), fn(Builder $q) => $q->where('modification_type', (string)$filters['modification_type']))
            ->when(!empty($filters['date_from']), fn(Builder $q) => $q->where('created_at', '>=', $filters['date_from']))
            ->when(!empty($filters['date_to']), fn(Builder $q) => $q->where('created_at', '<=', $filters['date_to'] . (strlen($filters['date_to']) <= 10 ? ' 23:59:59' : '')))
            ->orderBy('id', 'desc');
    }

    // =========================================================================
    // 6. REPORTE DE AUDITORÍA (FASE 18 INTEGRACIÓN)
    // =========================================================================

    public function getAuditReport(array $filters, User $actor, int $perPage = 25): LengthAwarePaginator
    {
        return $this->auditService->queryLogs($filters, $actor, $perPage);
    }

    public function exportAuditCsv(array $filters, User $actor): string
    {
        return $this->auditService->exportLogsCsv($filters, $actor);
    }
}
