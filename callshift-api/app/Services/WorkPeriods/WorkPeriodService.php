<?php

namespace App\Services\WorkPeriods;

use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Models\Department;
use App\Models\User;
use App\Enums\WorkPeriodStatus;
use App\Enums\WorkPeriodType;
use App\Enums\ScheduleVersionStatus;
use App\Services\Audit\AuditService;
use App\Services\Schedule\ScheduleVersionService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class WorkPeriodService
{
    /**
     * Obtiene listado paginado y filtrado de periodos laborales.
     */
    public function listWorkPeriods(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkPeriod::with([
            'department:id,name,code',
            'creator:id,username,email',
            'currentVersion',
        ])->withCount(['versions']);

        // 1. Búsqueda por nombre
        if (!empty($filters['search'])) {
            $search = trim($filters['search']);
            $query->where('name', 'like', "%{$search}%");
        }

        // 2. Filtro por estado
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // 3. Filtro por departamento
        if (isset($filters['department_id']) && $filters['department_id'] !== '') {
            $query->where('department_id', $filters['department_id']);
        }

        // 4. Filtro por rango de fechas
        if (!empty($filters['start_date'])) {
            $query->where('start_date', '>=', $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('end_date', '<=', $filters['end_date']);
        }

        // 5. Ordenamiento seguro con whitelist
        $sortField = in_array($filters['sort_by'] ?? '', ['id', 'name', 'start_date', 'end_date', 'status', 'created_at'], true)
            ? $filters['sort_by']
            : 'start_date';
        $sortOrder = strtolower($filters['sort_order'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sortField, $sortOrder)->paginate($perPage);
    }

    /**
     * Obtiene el listado compacto de periodos activos para selectores en malla.
     */
    public function getAllWorkPeriodsCompact(array $filters = []): Collection
    {
        $query = WorkPeriod::select([
            'id',
            'company_id',
            'department_id',
            'name',
            'period_type',
            'start_date',
            'end_date',
            'status',
            'current_version_id',
        ]);

        if (!empty($filters['department_id'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $filters['department_id']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    /**
     * Obtiene un periodo por ID con relaciones cargadas.
     */
    public function getWorkPeriodById(int $id): WorkPeriod
    {
        return WorkPeriod::with([
            'department:id,name,code',
            'creator:id,username,email',
            'currentVersion',
            'versions.creator:id,username,email',
            'versions.publisher:id,username,email',
        ])->withCount(['versions'])->findOrFail($id);
    }

    /**
     * Registra un nuevo periodo laboral, comprueba solapamientos e inicializa Version 1 en DRAFT.
     */
    public function createWorkPeriod(array $data, User $actor): WorkPeriod
    {
        // 1. Validar pertenencia del departamento al tenant
        if (!empty($data['department_id'])) {
            $dept = Department::where('company_id', $actor->company_id)->find($data['department_id']);
            if (!$dept) {
                throw ValidationException::withMessages([
                    'department_id' => 'El departamento seleccionado no pertenece a su empresa.',
                ]);
            }
        }

        // 2. Validar solapamiento temporal inclusivo
        $this->assertNoOverlap(
            $actor->company_id,
            $data['department_id'] ?? null,
            $data['start_date'],
            $data['end_date']
        );

        return DB::transaction(function () use ($data, $actor) {
            $data['company_id'] = $actor->company_id;
            $data['created_by'] = $actor->id;
            $data['status']     = $data['status'] ?? WorkPeriodStatus::DRAFT->value;
            $data['period_type'] = $data['period_type'] ?? WorkPeriodType::WEEKLY->value;

            $workPeriod = WorkPeriod::create($data);

            // Inicializar automáticamente ScheduleVersion V1 (Borrador inicial)
            $version = ScheduleVersion::create([
                'work_period_id'       => $workPeriod->id,
                'version_number'       => 1,
                'status'               => ScheduleVersionStatus::DRAFT,
                'parent_version_id'    => null,
                'change_summary'       => 'Versión inicial de planificación generada con el periodo.',
                'lock_version'         => 1,
                'created_by'           => $actor->id,
            ]);

            $workPeriod->update(['current_version_id' => $version->id]);
            $workPeriod->setRelation('currentVersion', $version);

            // Auditoría forense inmutable
            AuditService::logModelCreated(
                $workPeriod,
                "Periodo laboral '{$workPeriod->name}' ({$workPeriod->start_date} a {$workPeriod->end_date}) creado por '{$actor->username}'"
            );

            return $workPeriod;
        });
    }

    /**
     * Actualiza un periodo laboral respetando control de concurrencia optimista y solapamientos.
     */
    public function updateWorkPeriod(WorkPeriod $period, array $data, User $actor): WorkPeriod
    {
        // 1. Periodos cerrados no admiten cambios
        if ($period->status === WorkPeriodStatus::CLOSED) {
            throw ValidationException::withMessages([
                'status' => 'No es posible modificar un periodo laboral cerrado.',
            ]);
        }

        // 2. Control de concurrencia optimista
        if (isset($data['lock_version']) && $period->currentVersion) {
            if ((int)$data['lock_version'] !== (int)$period->currentVersion->lock_version) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => "Conflicto de concurrencia: El periodo fue modificado previamente por otro usuario. (Versión esperada: {$period->currentVersion->lock_version}, enviada: {$data['lock_version']})",
                ], Response::HTTP_CONFLICT));
            }
        }

        // 3. Validar departamento si cambia
        $departmentId = array_key_exists('department_id', $data) ? $data['department_id'] : $period->department_id;
        if (!empty($departmentId) && $departmentId !== $period->department_id) {
            $dept = Department::where('company_id', $actor->company_id)->find($departmentId);
            if (!$dept) {
                throw ValidationException::withMessages([
                    'department_id' => 'El departamento seleccionado no pertenece a su empresa.',
                ]);
            }
        }

        // 4. Validar solapamiento si cambian fechas o departamento
        $startDate = $data['start_date'] ?? $period->start_date->format('Y-m-d');
        $endDate   = $data['end_date'] ?? $period->end_date->format('Y-m-d');

        $this->assertNoOverlap(
            $actor->company_id,
            $departmentId,
            $startDate,
            $endDate,
            $period->id
        );

        return DB::transaction(function () use ($period, $data, $actor) {
            $oldValues = $period->getOriginal();

            // Descartar tenant o IDs en payload
            unset($data['id'], $data['company_id'], $data['created_by'], $data['lock_version']);

            $period->fill($data);
            $period->save();

            // Incrementar lock_version de la versión activa para evitar sobreescrituras concurrentes
            if ($period->currentVersion) {
                $period->currentVersion->increment('lock_version');
            }

            // Auditoría forense inmutable
            AuditService::logModelUpdated(
                $period,
                $oldValues,
                "Periodo laboral '{$period->name}' actualizado por '{$actor->username}'"
            );

            return $period->fresh(['department', 'creator', 'currentVersion']);
        });
    }

    /**
     * Cambia el estado de un periodo siguiendo la máquina de estados.
     * En caso de publicación, delega obligatoriamente en ScheduleVersionService::publishVersion.
     */
    public function changeWorkPeriodStatus(
        WorkPeriod $period,
        string $newStatusValue,
        ?string $reason,
        ?int $lockVersion,
        User $actor
    ): WorkPeriod {
        $conn = $period->getConnectionName();

        // 1. Si la acción solicitada es PUBLISHED -> Delegación obligatoria al único owner de publicación
        if ($newStatusValue === WorkPeriodStatus::PUBLISHED->value) {
            if ($lockVersion === null) {
                throw ValidationException::withMessages([
                    'lock_version' => 'El lock_version es obligatorio para publicar el periodo laboral.',
                ]);
            }

            $currentVersion = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $period->current_version_id)
                ->first();

            if (!$currentVersion) {
                throw ValidationException::withMessages([
                    'status' => 'El periodo laboral no tiene una versión de horario asignada para publicar.',
                ]);
            }

            if ($currentVersion->status !== ScheduleVersionStatus::REVIEW) {
                throw ValidationException::withMessages([
                    'status' => "No se puede publicar el periodo laboral: la versión de horario se encuentra en estado '{$currentVersion->status->value}' y debe pasar primero por revisión (REVIEW).",
                ]);
            }

            app(ScheduleVersionService::class)->publishVersion($currentVersion, (int)$lockVersion, $reason, $actor);

            return $period->fresh(['department', 'creator', 'currentVersion']);
        }

        // 2. Otras transiciones (ej: DRAFT, REVIEW, CLOSED)
        return $period->getConnection()->transaction(function () use ($period, $newStatusValue, $reason, $lockVersion, $actor, $conn) {
            $lockedPeriod = (new WorkPeriod())->setConnection($conn)->newQuery()
                ->where('id', $period->id)
                ->lockForUpdate()
                ->first();

            if (!$lockedPeriod) {
                throw new \RuntimeException('Periodo laboral no encontrado.');
            }

            $currentVersion = null;
            if ($lockedPeriod->current_version_id) {
                $currentVersion = (new ScheduleVersion())->setConnection($conn)->newQuery()
                    ->where('id', $lockedPeriod->current_version_id)
                    ->lockForUpdate()
                    ->first();
            }

            if ($newStatusValue === WorkPeriodStatus::CLOSED->value && $lockVersion === null && $currentVersion) {
                throw ValidationException::withMessages([
                    'lock_version' => 'El lock_version es obligatorio para cerrar el periodo laboral.',
                ]);
            }

            if ($lockVersion !== null && $currentVersion && (int)$lockVersion !== (int)$currentVersion->lock_version) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia al cambiar de estado. La versión de horario fue modificada por otro proceso.',
                ], Response::HTTP_CONFLICT));
            }

            $currentStatus = $lockedPeriod->status instanceof WorkPeriodStatus ? $lockedPeriod->status->value : (string)$lockedPeriod->status;

            $validTransitions = [
                WorkPeriodStatus::DRAFT->value     => [WorkPeriodStatus::GENERATED->value, WorkPeriodStatus::REVIEW->value, WorkPeriodStatus::CLOSED->value],
                WorkPeriodStatus::GENERATED->value => [WorkPeriodStatus::DRAFT->value, WorkPeriodStatus::REVIEW->value, WorkPeriodStatus::CLOSED->value],
                WorkPeriodStatus::REVIEW->value    => [WorkPeriodStatus::DRAFT->value, WorkPeriodStatus::CLOSED->value],
                WorkPeriodStatus::PUBLISHED->value => [WorkPeriodStatus::REVIEW->value, WorkPeriodStatus::CLOSED->value],
                WorkPeriodStatus::CLOSED->value    => [],
            ];

            if (!in_array($newStatusValue, $validTransitions[$currentStatus] ?? [], true)) {
                throw ValidationException::withMessages([
                    'status' => "Transición no permitida: No se puede cambiar el estado de '{$currentStatus}' a '{$newStatusValue}'.",
                ]);
            }

            $oldValues = $lockedPeriod->getOriginal();

            $lockedPeriod->status = $newStatusValue;
            $lockedPeriod->save();

            if ($currentVersion && $newStatusValue === WorkPeriodStatus::CLOSED->value) {
                $currentVersion->increment('lock_version');
            }

            $oldStatusStr = $oldValues['status'] instanceof WorkPeriodStatus ? $oldValues['status']->value : (string)$oldValues['status'];
            $detail = "Estado del periodo '{$lockedPeriod->name}' cambiado de {$oldStatusStr} a {$newStatusValue} por '{$actor->username}'";
            if ($reason) {
                $detail .= ". Motivo: {$reason}";
            }

            AuditService::logModelUpdated(
                $lockedPeriod,
                $oldValues,
                $detail
            );

            return $lockedPeriod->fresh(['department', 'creator', 'currentVersion']);
        });
    }

    /**
     * Elimina lógicamente un periodo si se encuentra en estado DRAFT o GENERATED sin versiones publicadas.
     */
    public function deleteWorkPeriod(WorkPeriod $period, User $actor): void
    {
        if ($period->status === WorkPeriodStatus::PUBLISHED || $period->status === WorkPeriodStatus::CLOSED) {
            throw ValidationException::withMessages([
                'period' => "No es posible eliminar el periodo '{$period->name}' porque se encuentra en estado {$period->status->value}.",
            ]);
        }

        DB::transaction(function () use ($period, $actor) {
            $period->delete();

            AuditService::logModelDeleted(
                $period,
                "Periodo laboral '{$period->name}' eliminado por '{$actor->username}'"
            );
        });
    }

    /**
     * Comprueba que no existan solapamientos de fechas inclusivas con otros periodos de la empresa/área.
     */
    public function assertNoOverlap(
        int $companyId,
        ?int $departmentId,
        string $startDate,
        string $endDate,
        ?int $ignorePeriodId = null
    ): void {
        $query = WorkPeriod::where('company_id', $companyId)
            ->where('status', '!=', WorkPeriodStatus::CLOSED->value);

        if ($ignorePeriodId) {
            $query->where('id', '!=', $ignorePeriodId);
        }

        if (!empty($departmentId)) {
            $query->where(function ($q) use ($departmentId) {
                $q->whereNull('department_id')
                  ->orWhere('department_id', $departmentId);
            });
        } else {
            $query->whereNull('department_id');
        }

        $overlapping = $query->where(function ($q) use ($startDate, $endDate) {
            $q->where('start_date', '<=', $endDate)
              ->where('end_date', '>=', $startDate);
        })->first();

        if ($overlapping) {
            $overlapStart = $overlapping->start_date instanceof \Carbon\Carbon
                ? $overlapping->start_date->format('Y-m-d')
                : (string)$overlapping->start_date;
            $overlapEnd = $overlapping->end_date instanceof \Carbon\Carbon
                ? $overlapping->end_date->format('Y-m-d')
                : (string)$overlapping->end_date;

            throw ValidationException::withMessages([
                'start_date' => "Existe un solapamiento con el periodo activo '{$overlapping->name}' ({$overlapStart} a {$overlapEnd}).",
            ]);
        }
    }
}
