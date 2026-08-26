<?php

namespace App\Services\Schedule;

use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use App\Models\User;
use App\Enums\WorkPeriodStatus;
use App\Enums\ScheduleVersionStatus;
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Conflicts\ConflictDetectionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;

class ScheduleVersionService
{
    /**
     * Obtiene el listado ordenado de versiones para un periodo laboral.
     */
    public function listVersions(WorkPeriod $period, User $actor): Collection
    {
        $this->assertTenantOwnership($period->company_id, $actor->company_id);

        $conn = $period->getConnectionName();

        return (new ScheduleVersion())->setConnection($conn)->newQuery()
            ->where('work_period_id', $period->id)
            ->with(['creator:id,username,email', 'publisher:id,username,email', 'parentVersion:id,version_number'])
            ->orderBy('version_number', 'desc')
            ->get();
    }

    /**
     * Obtiene el detalle de una versión específica.
     */
    public function getVersion(int $id, User $actor): ScheduleVersion
    {
        $version = ScheduleVersion::with([
            'workPeriod',
            'creator:id,username,email',
            'publisher:id,username,email',
            'parentVersion:id,version_number',
        ])->findOrFail($id);

        $this->assertTenantOwnership($version->workPeriod->company_id, $actor->company_id);

        return $version;
    }

    /**
     * Crea un nuevo borrador (DRAFT) correlativo, clonando opcionalmente desde una versión base.
     */
    public function createDraftFromVersion(
        WorkPeriod $period,
        ?ScheduleVersion $sourceVersion,
        ?string $changeSummary,
        User $actor
    ): ScheduleVersion {
        // TENANT-INVARIANT
        $this->assertTenantOwnership($period->company_id, $actor->company_id);

        if ($sourceVersion) {
            if ($sourceVersion->work_period_id !== $period->id) {
                throw ValidationException::withMessages([
                    'source_version_id' => 'La versión origen no pertenece al periodo laboral especificado.',
                ]);
            }
            $this->assertTenantOwnership($sourceVersion->workPeriod->company_id, $actor->company_id);
        }

        return $period->getConnection()->transaction(function () use ($period, $sourceVersion, $changeSummary, $actor) {
            $conn = $period->getConnectionName();

            // Bloqueo pesimista del periodo
            $lockedPeriod = (new WorkPeriod())->setConnection($conn)->newQuery()
                ->where('id', $period->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantOwnership($lockedPeriod->company_id, $actor->company_id);

            if ($lockedPeriod->status === WorkPeriodStatus::CLOSED) {
                throw ValidationException::withMessages([
                    'work_period' => 'No se pueden crear nuevas versiones en un periodo laboral CERRADO.',
                ]);
            }

            // Numeración correlativa atómica segura
            $maxVersion = (int)(new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('work_period_id', $period->id)
                ->max('version_number');
            $nextNumber = $maxVersion + 1;

            // Instanciar nueva versión DRAFT
            $newVersion = (new ScheduleVersion())->setConnection($conn);
            $newVersion->fill([
                'work_period_id'       => $period->id,
                'version_number'       => $nextNumber,
                'status'               => ScheduleVersionStatus::DRAFT,
                'parent_version_id'    => $sourceVersion?->id,
                'change_summary'       => $changeSummary ?? ($sourceVersion ? "Borrador generado a partir de V{$sourceVersion->version_number}" : "Versión V{$nextNumber}"),
                'lock_version'         => 1,
                'created_by'           => $actor->id,
            ]);
            $newVersion->save();

            // Deep-copy de asignaciones si existe versión origen
            if ($sourceVersion) {
                $this->deepCopyAssignments($sourceVersion, $newVersion, $conn);
            }

            // Auditoría transaccional
            AuditService::log(
                AuditAction::CREATE,
                ScheduleVersion::class,
                $newVersion->id,
                "Borrador de horario V{$newVersion->version_number} creado por '{$actor->username}'" . ($sourceVersion ? " a partir de V{$sourceVersion->version_number}" : ""),
                null,
                ['version_number' => $nextNumber, 'parent_version_id' => $sourceVersion?->id],
                $period->company_id,
                $conn
            );

            return $newVersion->fresh(['creator', 'parentVersion']);
        });
    }

    /**
     * Transiciona una versión de DRAFT a REVIEW con control optimista de concurrencia.
     */
    public function reviewVersion(
        ScheduleVersion $version,
        int $lockVersion,
        ?string $notes,
        User $actor
    ): ScheduleVersion {
        // TENANT-INVARIANT
        $this->assertTenantOwnership($version->workPeriod->company_id, $actor->company_id);

        return $version->getConnection()->transaction(function () use ($version, $lockVersion, $notes, $actor) {
            $conn = $version->getConnectionName();

            $lockedVersion = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $version->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantOwnership($lockedVersion->workPeriod->company_id, $actor->company_id);

            // Control de concurrencia optimista
            if ((int)$lockVersion !== (int)$lockedVersion->lock_version) {
                throw new HttpResponseException(response()->json([
                    'status'               => 'error',
                    'message'              => 'Conflicto de concurrencia: La versión de horario fue modificada por otro usuario.',
                    'current_lock_version' => $lockedVersion->lock_version,
                ], Response::HTTP_CONFLICT));
            }

            if ($lockedVersion->status !== ScheduleVersionStatus::DRAFT) {
                throw ValidationException::withMessages([
                    'status' => "Transición no permitida: No se puede enviar a revisión una versión en estado '{$lockedVersion->status->value}'.",
                ]);
            }

            $affected = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $lockedVersion->id)
                ->where('lock_version', (int)$lockVersion)
                ->update([
                    'status'       => ScheduleVersionStatus::REVIEW->value,
                    'lock_version' => (int)$lockVersion + 1,
                    'updated_at'   => now(),
                ]);

            if ($affected === 0) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia detectado al enviar a revisión.',
                ], Response::HTTP_CONFLICT));
            }

            AuditService::log(
                AuditAction::UPDATE,
                ScheduleVersion::class,
                $lockedVersion->id,
                "Versión V{$lockedVersion->version_number} enviada a revisión (REVIEW) por '{$actor->username}'" . ($notes ? ". Notas: {$notes}" : ""),
                ['status' => 'DRAFT', 'lock_version' => (int)$lockVersion],
                ['status' => 'REVIEW', 'lock_version' => (int)$lockVersion + 1],
                $lockedVersion->workPeriod->company_id,
                $conn
            );

            return $lockedVersion->fresh(['creator', 'parentVersion']);
        });
    }

    /**
     * Retorno controlado de REVIEW a DRAFT para correcciones.
     */
    public function returnToDraft(
        ScheduleVersion $version,
        int $lockVersion,
        ?string $reason,
        User $actor
    ): ScheduleVersion {
        // TENANT-INVARIANT
        $this->assertTenantOwnership($version->workPeriod->company_id, $actor->company_id);

        return $version->getConnection()->transaction(function () use ($version, $lockVersion, $reason, $actor) {
            $conn = $version->getConnectionName();

            $lockedVersion = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $version->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantOwnership($lockedVersion->workPeriod->company_id, $actor->company_id);

            // Control de concurrencia optimista
            if ((int)$lockVersion !== (int)$lockedVersion->lock_version) {
                throw new HttpResponseException(response()->json([
                    'status'               => 'error',
                    'message'              => 'Conflicto de concurrencia: La versión de horario fue modificada por otro usuario.',
                    'current_lock_version' => $lockedVersion->lock_version,
                ], Response::HTTP_CONFLICT));
            }

            if ($lockedVersion->status !== ScheduleVersionStatus::REVIEW) {
                throw ValidationException::withMessages([
                    'status' => "Transición no permitida: Solo versiones en estado 'REVIEW' pueden ser devueltas a borrador.",
                ]);
            }

            $affected = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $lockedVersion->id)
                ->where('lock_version', (int)$lockVersion)
                ->update([
                    'status'       => ScheduleVersionStatus::DRAFT->value,
                    'lock_version' => (int)$lockVersion + 1,
                    'updated_at'   => now(),
                ]);

            if ($affected === 0) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia detectado al devolver a borrador.',
                ], Response::HTTP_CONFLICT));
            }

            AuditService::log(
                AuditAction::UPDATE,
                ScheduleVersion::class,
                $lockedVersion->id,
                "Versión V{$lockedVersion->version_number} devuelta a borrador (DRAFT) por '{$actor->username}'" . ($reason ? ". Motivo: {$reason}" : ""),
                ['status' => 'REVIEW', 'lock_version' => (int)$lockVersion],
                ['status' => 'DRAFT', 'lock_version' => (int)$lockVersion + 1],
                $lockedVersion->workPeriod->company_id,
                $conn
            );

            return $lockedVersion->fresh(['creator', 'parentVersion']);
        });
    }

    /**
     * Publicación atómica oficial de una versión en REVIEW (Único Owner de Publicación en el sistema).
     */
    public function publishVersion(
        ScheduleVersion $version,
        int $lockVersion,
        ?string $changeSummary,
        User $actor
    ): ScheduleVersion {
        // TENANT-INVARIANT
        $this->assertTenantOwnership($version->workPeriod->company_id, $actor->company_id);

        return $version->getConnection()->transaction(function () use ($version, $lockVersion, $changeSummary, $actor) {
            $conn = $version->getConnectionName();

            // Bloqueo pesimista simultáneo del aggregate WorkPeriod y ScheduleVersion
            $lockedPeriod = (new WorkPeriod())->setConnection($conn)->newQuery()
                ->where('id', $version->work_period_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantOwnership($lockedPeriod->company_id, $actor->company_id);

            if ($lockedPeriod->status === WorkPeriodStatus::CLOSED) {
                throw ValidationException::withMessages([
                    'status' => 'No se puede publicar un horario en un periodo laboral CERRADO.',
                ]);
            }

            $lockedVersion = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $version->id)
                ->lockForUpdate()
                ->firstOrFail();

            // Adquirir bloqueo exclusivo atómico de escritura en el aggregate
            (new WorkPeriod())->setConnection($conn)->newQuery()->where('id', $lockedPeriod->id)->update(['updated_at' => now()]);

            // Control de concurrencia optimista
            if ((int)$lockVersion !== (int)$lockedVersion->lock_version) {
                throw new HttpResponseException(response()->json([
                    'status'               => 'error',
                    'message'              => 'Conflicto de concurrencia al cambiar de estado. La versión de horario fue modificada por otro proceso.',
                    'current_lock_version' => $lockedVersion->lock_version,
                ], Response::HTTP_CONFLICT));
            }

            // Exigir estado REVIEW estricto
            if ($lockedVersion->status !== ScheduleVersionStatus::REVIEW) {
                throw ValidationException::withMessages([
                    'status' => "Transición no permitida: No se puede publicar una versión en estado '{$lockedVersion->status->value}'. Debe estar en estado REVIEW.",
                ]);
            }

            // Validación de conflictos en tiempo real (Zero TOCTOU Window)
            $conflictService = app(ConflictDetectionService::class);
            $conflicts = $conflictService->validateVersion($lockedVersion, $actor);
            $activeHard = $conflicts->filter(fn($c) => 
                (is_object($c->severity) ? $c->severity->value : $c->severity) === 'HARD_CONFLICT' &&
                (is_object($c->status) ? $c->status->value : $c->status) === 'ACTIVE'
            );

            if ($activeHard->isNotEmpty()) {
                $count = $activeHard->count();
                throw ValidationException::withMessages([
                    'conflicts' => "No es posible publicar el horario: existen {$count} conflicto(s) crítico(s) (HARD) activos sin resolver.",
                ]);
            }

            // Invariante I2 (UNIQUE-PUBLISHED): Archivar versión publicada previa
            $prevPublished = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('work_period_id', $lockedPeriod->id)
                ->where('status', ScheduleVersionStatus::PUBLISHED->value)
                ->where('id', '!=', $lockedVersion->id)
                ->first();

            if ($prevPublished) {
                $prevPublished->update([
                    'status'     => ScheduleVersionStatus::ARCHIVED->value,
                    'updated_at' => now(),
                ]);

                AuditService::log(
                    AuditAction::UPDATE,
                    ScheduleVersion::class,
                    $prevPublished->id,
                    "Versión V{$prevPublished->version_number} archivada automáticamente al publicarse V{$lockedVersion->version_number}",
                    ['status' => 'PUBLISHED'],
                    ['status' => 'ARCHIVED'],
                    $lockedPeriod->company_id,
                    $conn
                );
            }

            // Invariante I1 (PUBLISH-OWNER): Mutación atómica de la versión a PUBLISHED
            $affected = (new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('id', $lockedVersion->id)
                ->where('lock_version', (int)$lockVersion)
                ->update([
                    'status'         => ScheduleVersionStatus::PUBLISHED->value,
                    'published_by'   => $actor->id,
                    'published_at'   => now(),
                    'change_summary' => $changeSummary ?? $lockedVersion->change_summary,
                    'lock_version'   => (int)$lockVersion + 1,
                    'updated_at'     => now(),
                ]);

            if ($affected === 0) {
                throw new HttpResponseException(response()->json([
                    'status'  => 'error',
                    'message' => 'Conflicto de concurrencia detectado al persistir la versión de horario.',
                ], Response::HTTP_CONFLICT));
            }

            // Invariante I3 (CURRENT-VERSION): Actualizar aggregate WorkPeriod
            $lockedPeriod->status = WorkPeriodStatus::PUBLISHED;
            $lockedPeriod->current_version_id = $lockedVersion->id;
            $lockedPeriod->save();

            // Auditoría forense inmutable
            AuditService::log(
                AuditAction::UPDATE,
                ScheduleVersion::class,
                $lockedVersion->id,
                "Versión V{$lockedVersion->version_number} publicada oficialmente por '{$actor->username}'",
                ['status' => 'REVIEW', 'lock_version' => (int)$lockVersion],
                ['status' => 'PUBLISHED', 'lock_version' => (int)$lockVersion + 1],
                $lockedPeriod->company_id,
                $conn
            );

            return $lockedVersion->fresh(['creator', 'publisher', 'parentVersion']);
        });
    }

    /**
     * Restauración no destructiva de una versión histórica como un nuevo borrador.
     */
    public function restoreVersion(
        WorkPeriod $period,
        ScheduleVersion $targetVersion,
        string $reason,
        User $actor
    ): ScheduleVersion {
        // TENANT-INVARIANT
        $this->assertTenantOwnership($period->company_id, $actor->company_id);

        if ($targetVersion->work_period_id !== $period->id) {
            throw ValidationException::withMessages([
                'target_version_id' => 'La versión objetivo no pertenece al periodo laboral indicado.',
            ]);
        }

        $this->assertTenantOwnership($targetVersion->workPeriod->company_id, $actor->company_id);

        if (!in_array($targetVersion->status, [ScheduleVersionStatus::PUBLISHED, ScheduleVersionStatus::ARCHIVED], true)) {
            throw ValidationException::withMessages([
                'target_version_id' => 'Solo se pueden restaurar versiones que hayan sido previamente publicadas o archivadas.',
            ]);
        }

        if (mb_strlen(trim($reason)) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'El motivo de restauración debe contener al menos 5 caracteres descriptivos.',
            ]);
        }

        return $period->getConnection()->transaction(function () use ($period, $targetVersion, $reason, $actor) {
            $conn = $period->getConnectionName();

            // Bloqueo pesimista del periodo
            $lockedPeriod = (new WorkPeriod())->setConnection($conn)->newQuery()
                ->where('id', $period->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertTenantOwnership($lockedPeriod->company_id, $actor->company_id);

            if ($lockedPeriod->status === WorkPeriodStatus::CLOSED) {
                throw ValidationException::withMessages([
                    'work_period' => 'No se puede restaurar un horario en un periodo laboral CERRADO.',
                ]);
            }

            $maxVersion = (int)(new ScheduleVersion())->setConnection($conn)->newQuery()
                ->where('work_period_id', $period->id)
                ->max('version_number');
            $nextNumber = $maxVersion + 1;

            $newVersion = (new ScheduleVersion())->setConnection($conn);
            $newVersion->fill([
                'work_period_id'       => $period->id,
                'version_number'       => $nextNumber,
                'status'               => ScheduleVersionStatus::DRAFT,
                'parent_version_id'    => $targetVersion->id,
                'change_summary'       => "Restauración de versión V{$targetVersion->version_number}: {$reason}",
                'lock_version'         => 1,
                'created_by'           => $actor->id,
            ]);
            $newVersion->save();

            // Deep-copy de asignaciones de la versión objetivo
            $this->deepCopyAssignments($targetVersion, $newVersion, $conn);

            // Auditoría transaccional unificada
            AuditService::log(
                AuditAction::CREATE,
                ScheduleVersion::class,
                $newVersion->id,
                "Restauración de versión V{$targetVersion->version_number} generó borrador V{$newVersion->version_number} por '{$actor->username}'",
                null,
                ['restored_from_version_id' => $targetVersion->id, 'reason' => $reason],
                $period->company_id,
                $conn
            );

            return $newVersion->fresh(['creator', 'parentVersion']);
        });
    }

    /**
     * Comparador semántico de diferencias entre dos versiones de un mismo periodo laboral.
     */
    public function compareVersions(
        ScheduleVersion $versionA,
        ScheduleVersion $versionB,
        User $actor
    ): array {
        // TENANT-INVARIANT
        $this->assertTenantOwnership($versionA->workPeriod->company_id, $actor->company_id);
        $this->assertTenantOwnership($versionB->workPeriod->company_id, $actor->company_id);

        if ($versionA->work_period_id !== $versionB->work_period_id) {
            throw ValidationException::withMessages([
                'version_b' => 'Solo se pueden comparar versiones pertenecientes al mismo periodo laboral.',
            ]);
        }

        if ($versionA->id === $versionB->id) {
            throw ValidationException::withMessages([
                'version_b' => 'No se puede comparar una versión consigo misma.',
            ]);
        }

        $conn = $versionA->getConnectionName();

        $assignmentsA = (new ScheduleAssignment())->setConnection($conn)->newQuery()
            ->where('schedule_version_id', $versionA->id)
            ->with(['shiftType', 'employee:id,first_name,last_name'])
            ->get()
            ->keyBy(fn($a) => $a->employee_id . '_' . ($a->getRawOriginal('date') ?? $a->date->format('Y-m-d')));

        $assignmentsB = (new ScheduleAssignment())->setConnection($conn)->newQuery()
            ->where('schedule_version_id', $versionB->id)
            ->with(['shiftType', 'employee:id,first_name,last_name'])
            ->get()
            ->keyBy(fn($b) => $b->employee_id . '_' . ($b->getRawOriginal('date') ?? $b->date->format('Y-m-d')));

        $added     = [];
        $removed   = [];
        $modified  = [];
        $unchanged = [];

        // Comparar A vs B
        foreach ($assignmentsB as $key => $itemB) {
            if (!$assignmentsA->has($key)) {
                $added[] = $this->formatDiffItem($itemB);
            } else {
                $itemA = $assignmentsA->get($key);
                if ($this->isAssignmentDifferent($itemA, $itemB)) {
                    $modified[] = [
                        'key'      => $key,
                        'employee' => ['id' => $itemB->employee_id, 'name' => "{$itemB->employee?->first_name} {$itemB->employee?->last_name}"],
                        'date'     => $itemB->getRawOriginal('date') ?? $itemB->date->format('Y-m-d'),
                        'before'   => $this->formatDiffItem($itemA),
                        'after'    => $this->formatDiffItem($itemB),
                    ];
                } else {
                    $unchanged[] = $this->formatDiffItem($itemB);
                }
            }
        }

        foreach ($assignmentsA as $key => $itemA) {
            if (!$assignmentsB->has($key)) {
                $removed[] = $this->formatDiffItem($itemA);
            }
        }

        $totalHoursA = (float)$assignmentsA->sum('total_hours');
        $totalHoursB = (float)$assignmentsB->sum('total_hours');

        return [
            'version_a' => [
                'id'             => $versionA->id,
                'version_number' => $versionA->version_number,
                'status'         => $versionA->status->value,
                'total_hours'    => $totalHoursA,
                'assignments'    => $assignmentsA->count(),
            ],
            'version_b' => [
                'id'             => $versionB->id,
                'version_number' => $versionB->version_number,
                'status'         => $versionB->status->value,
                'total_hours'    => $totalHoursB,
                'assignments'    => $assignmentsB->count(),
            ],
            'diff' => [
                'added'       => $added,
                'removed'     => $removed,
                'modified'    => $modified,
                'unchanged'   => $unchanged,
                'hours_delta' => round($totalHoursB - $totalHoursA, 2),
            ],
        ];
    }

    /**
     * Clona fielmente los registros de asignaciones entre versiones.
     */
    private function deepCopyAssignments(ScheduleVersion $source, ScheduleVersion $target, string $conn): void
    {
        $assignments = (new ScheduleAssignment())->setConnection($conn)->newQuery()
            ->where('schedule_version_id', $source->id)
            ->get();

        foreach ($assignments as $assignment) {
            (new ScheduleAssignment())->setConnection($conn)->newQuery()->create([
                'schedule_version_id' => $target->id,
                'employee_id'         => $assignment->employee_id,
                'date'                => $assignment->getRawOriginal('date') ?? $assignment->date->format('Y-m-d'),
                'day_type'            => $assignment->day_type,
                'shift_type_id'       => $assignment->shift_type_id,
                'start_time'          => $assignment->start_time,
                'end_time'            => $assignment->end_time,
                'starts_at'           => $assignment->starts_at,
                'ends_at'             => $assignment->ends_at,
                'break_start'         => $assignment->break_start,
                'break_end'           => $assignment->break_end,
                'total_hours'         => $assignment->total_hours,
                'is_custom'           => $assignment->is_custom,
                'notes'               => $assignment->notes,
            ]);
        }
    }

    private function isAssignmentDifferent(ScheduleAssignment $a, ScheduleAssignment $b): bool
    {
        return $a->shift_type_id !== $b->shift_type_id
            || $a->day_type !== $b->day_type
            || $a->start_time !== $b->start_time
            || $a->end_time !== $b->end_time
            || (float)$a->total_hours !== (float)$b->total_hours
            || (bool)$a->is_custom !== (bool)$b->is_custom;
    }

    private function formatDiffItem(ScheduleAssignment $item): array
    {
        return [
            'id'          => $item->id,
            'employee_id' => $item->employee_id,
            'employee'    => ['id' => $item->employee_id, 'name' => "{$item->employee?->first_name} {$item->employee?->last_name}"],
            'date'        => $item->getRawOriginal('date') ?? $item->date->format('Y-m-d'),
            'day_type'    => is_object($item->day_type) ? $item->day_type->value : (string)$item->day_type,
            'shift_type'  => $item->shiftType ? ['id' => $item->shiftType->id, 'name' => $item->shiftType->name, 'code' => $item->shiftType->code] : null,
            'start_time'  => $item->start_time,
            'end_time'    => $item->end_time,
            'total_hours' => (float)$item->total_hours,
            'is_custom'   => (bool)$item->is_custom,
        ];
    }

    private function assertTenantOwnership(?int $entityCompanyId, int $actorCompanyId): void
    {
        if ($entityCompanyId === null || $entityCompanyId !== $actorCompanyId) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'Acceso denegado: La entidad solicitada pertenece a otra empresa.',
            ], Response::HTTP_FORBIDDEN));
        }
    }
}
