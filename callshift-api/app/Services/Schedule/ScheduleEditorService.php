<?php

namespace App\Services\Schedule;

use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\User;
use App\Enums\DayType;
use App\Enums\WorkPeriodStatus;
use App\Services\Audit\AuditService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ScheduleEditorService
{
    /**
     * Obtiene el contexto completo de la malla (WorkPeriod, Versión, Días, Empleados, Turnos, Asignaciones).
     */
    public function getGridData(int $workPeriodId, ?int $versionId, User $actor): array
    {
        $workPeriod = WorkPeriod::where('company_id', $actor->company_id)
            ->with(['department'])
            ->findOrFail($workPeriodId);

        // Resolver la versión de horario
        if ($versionId) {
            $version = ScheduleVersion::where('work_period_id', $workPeriod->id)->findOrFail($versionId);
        } elseif ($workPeriod->current_version_id) {
            $version = ScheduleVersion::where('work_period_id', $workPeriod->id)->find($workPeriod->current_version_id);
        }

        if (empty($version)) {
            $version = ScheduleVersion::where('work_period_id', $workPeriod->id)
                ->orderBy('version_number', 'desc')
                ->firstOrFail();
        }

        // Cargar colaboradores en el alcance del periodo
        $empQuery = Employee::where('company_id', $actor->company_id)
            ->where('status', 'ACTIVE')
            ->with(['department:id,name,code', 'position:id,name,code']);

        if (!empty($workPeriod->department_id)) {
            $empQuery->where('department_id', $workPeriod->department_id);
        }

        $employees = $empQuery->orderBy('first_name')->orderBy('last_name')->get();

        // Cargar turnos activos
        $shiftTypes = ShiftType::where('company_id', $actor->company_id)
            ->where('status', 'ACTIVE')
            ->orderBy('start_time')
            ->get();

        // Cargar asignaciones de la versión con eager loading
        $assignments = ScheduleAssignment::where('schedule_version_id', $version->id)
            ->with(['shiftType'])
            ->get();

        return [
            'work_period' => $workPeriod,
            'version'     => $version,
            'employees'   => $employees,
            'shift_types' => $shiftTypes,
            'assignments' => $assignments,
        ];
    }

    /**
     * Crea o actualiza la asignación de una celda con control de concurrencia optimista y cálculo temporal.
     */
    public function upsertAssignment(ScheduleVersion $version, array $data, User $actor): array
    {
        $conn = $version->getConnectionName();
        $workPeriod = $version->workPeriod ?: (new WorkPeriod())->setConnection($conn)->find($version->work_period_id);

        // 1. Validar pertenencia multi-tenant
        if ($workPeriod->company_id !== $actor->company_id) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'Acceso denegado: El horario pertenece a otra empresa.',
            ], Response::HTTP_FORBIDDEN));
        }

        // 2. Validar inmutabilidad
        if ($version->isImmutable()) {
            throw ValidationException::withMessages([
                'version' => "No se pueden modificar asignaciones en una versión en estado {$version->status->value}.",
            ]);
        }

        if ($workPeriod->status === WorkPeriodStatus::CLOSED) {
            throw ValidationException::withMessages([
                'work_period' => 'No se pueden modificar asignaciones en un periodo cerrado.',
            ]);
        }

        // 3. Validar rango de fechas inclusivo [start_date, end_date]
        $assignDate = Carbon::parse($data['date'])->format('Y-m-d');
        $startStr   = $workPeriod->start_date instanceof Carbon ? $workPeriod->start_date->format('Y-m-d') : substr((string)$workPeriod->start_date, 0, 10);
        $endStr     = $workPeriod->end_date instanceof Carbon ? $workPeriod->end_date->format('Y-m-d') : substr((string)$workPeriod->end_date, 0, 10);

        if ($assignDate < $startStr || $assignDate > $endStr) {
            throw ValidationException::withMessages([
                'date' => "La fecha de asignación ({$assignDate}) se encuentra fuera del rango del periodo laboral ({$startStr} a {$endStr}).",
            ]);
        }

        // 4. Validar empleado en tenant y alcance de departamento
        $empModel = new Employee();
        $empModel->setConnection($version->getConnectionName());
        $employee = $empModel->newQuery()->where('company_id', $actor->company_id)->findOrFail($data['employee_id']);
        if (!empty($workPeriod->department_id) && $employee->department_id !== $workPeriod->department_id) {
            throw ValidationException::withMessages([
                'employee_id' => "El colaborador '{$employee->full_name}' no pertenece al departamento del periodo laboral.",
            ]);
        }

        // 5. Determinar tipo de día y turno
        $dayType = $data['day_type'] ?? DayType::WORK->value;
        $shiftTypeId = $data['shift_type_id'] ?? null;
        $shiftType = null;

        $startTime  = null;
        $endTime    = null;
        $startsAt   = null;
        $endsAt     = null;
        $totalHours = 0.00;

        if ($dayType === DayType::WORK->value && $shiftTypeId) {
            $shiftModel = new ShiftType();
            $shiftModel->setConnection($version->getConnectionName());
            $shiftType = $shiftModel->newQuery()->where('company_id', $actor->company_id)->findOrFail($shiftTypeId);

            $startTime  = substr($shiftType->start_time, 0, 8);
            $endTime    = substr($shiftType->end_time, 0, 8);
            $totalHours = (float) $shiftType->total_work_hours;

            $startsAt = Carbon::parse("{$assignDate} {$startTime}");

            if ($shiftType->crosses_midnight) {
                $endsAt = Carbon::parse("{$assignDate} {$endTime}")->addDay();
            } else {
                $endsAt = Carbon::parse("{$assignDate} {$endTime}");
            }
        } elseif ($dayType !== DayType::WORK->value) {
            $shiftTypeId = null;
        }

        $incomingLock = (int) $data['lock_version'];

        return $version->getConnection()->transaction(function () use (
            $version,
            $employee,
            $assignDate,
            $dayType,
            $shiftTypeId,
            $shiftType,
            $startTime,
            $endTime,
            $startsAt,
            $endsAt,
            $totalHours,
            $data,
            $incomingLock,
            $actor
        ) {
            // Control de concurrencia optimista y bloqueo de fila
            $lockedVersion = $version->newQuery()->where('id', $version->id)->lockForUpdate()->first();
            if (!$lockedVersion) {
                throw new \RuntimeException('Versión de horario no encontrada.');
            }

            $currentLock = (int) $lockedVersion->lock_version;
            if ($incomingLock !== $currentLock) {
                throw new HttpResponseException(response()->json([
                    'status'               => 'error',
                    'message'              => "Conflicto de concurrencia: El horario fue modificado por otro usuario. (Versión esperada: {$currentLock}, enviada: {$incomingLock})",
                    'current_lock_version' => $currentLock,
                ], Response::HTTP_CONFLICT));
            }

            $affected = $version->newQuery()->where('id', $version->id)
                ->where('lock_version', $incomingLock)
                ->update(['lock_version' => $incomingLock + 1]);

            if ($affected === 0) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia al actualizar el horario.',
                ], Response::HTTP_CONFLICT));
            }

            // Crear o actualizar la asignación de celda
            $assignmentModel = new ScheduleAssignment();
            $assignmentModel->setConnection($version->getConnectionName());

            $assignment = $assignmentModel->newQuery()->updateOrCreate(
                [
                    'schedule_version_id' => $version->id,
                    'employee_id'         => $employee->id,
                    'date'                => $assignDate,
                ],
                [
                    'day_type'      => $dayType,
                    'shift_type_id' => $shiftTypeId,
                    'start_time'    => $startTime,
                    'end_time'      => $endTime,
                    'starts_at'     => $startsAt,
                    'ends_at'       => $endsAt,
                    'total_hours'   => $totalHours,
                    'is_custom'     => false,
                    'notes'         => $data['notes'] ?? null,
                ]
            );

            $assignment->load(['shiftType']);

            // Auditoría forense
            AuditService::logModelUpdated(
                $assignment,
                [],
                "Asignación de '{$employee->first_name} {$employee->last_name}' el {$assignDate} actualizada por '{$actor->username}'"
            );

            return [
                'assignment'   => $assignment,
                'lock_version' => $incomingLock + 1,
            ];
        });
    }

    /**
     * Elimina / libera la asignación de una celda.
     */
    public function deleteAssignment(ScheduleVersion $version, int $assignmentId, int $lockVersion, User $actor): array
    {
        $workPeriod = $version->workPeriod;

        if ($workPeriod->company_id !== $actor->company_id) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'Acceso denegado.',
            ], Response::HTTP_FORBIDDEN));
        }

        if ($version->isImmutable()) {
            throw ValidationException::withMessages([
                'version' => 'No se puede modificar una versión publicada o archivada.',
            ]);
        }

        // Blindaje contra IDOR: verificar pertenencia del assignmentId a esta versión
        $assignmentModel = new ScheduleAssignment();
        $assignmentModel->setConnection($version->getConnectionName());
        $assignment = $assignmentModel->newQuery()->where('schedule_version_id', $version->id)
            ->with(['employee'])
            ->findOrFail($assignmentId);

        return $version->getConnection()->transaction(function () use ($version, $assignment, $lockVersion, $actor) {
            $lockedVersion = $version->newQuery()->where('id', $version->id)->lockForUpdate()->first();
            if (!$lockedVersion) {
                throw new \RuntimeException('Versión de horario no encontrada.');
            }

            $currentLock = (int) $lockedVersion->lock_version;
            if ($lockVersion !== $currentLock) {
                throw new HttpResponseException(response()->json([
                    'status'               => 'error',
                    'message'              => 'Conflicto de concurrencia al eliminar la asignación.',
                    'current_lock_version' => $currentLock,
                ], Response::HTTP_CONFLICT));
            }

            $affected = $version->newQuery()->where('id', $version->id)
                ->where('lock_version', $lockVersion)
                ->update(['lock_version' => $lockVersion + 1]);

            if ($affected === 0) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia al actualizar el horario.',
                ], Response::HTTP_CONFLICT));
            }

            $empName = $assignment->employee ? $assignment->employee->first_name . ' ' . $assignment->employee->last_name : 'Colaborador';
            $dateStr = $assignment->date;

            $assignment->delete();

            AuditService::logModelDeleted(
                $assignment,
                "Asignación de '{$empName}' el {$dateStr} liberada por '{$actor->username}'"
            );

            return [
                'lock_version' => $lockVersion + 1,
            ];
        });
    }
}
