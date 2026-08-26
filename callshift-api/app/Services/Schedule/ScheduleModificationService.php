<?php

namespace App\Services\Schedule;

use App\Models\ScheduleModification;
use App\Models\ModificationEvidence;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\User;
use App\Enums\ScheduleVersionStatus;
use App\Enums\ModificationType;
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Conflicts\ConflictDetectionService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;
use DomainException;

class ScheduleModificationService
{
    public const MAX_FILE_SIZE_BYTES = 10485760; // 10 MB
    public const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/png',
        'image/jpeg',
        'image/jpg',
    ];
    public const ALLOWED_EXTENSIONS = [
        'pdf',
        'png',
        'jpg',
        'jpeg',
    ];

    public function __construct(
        protected ScheduleVersionService $versionService,
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * Registra una modificación controlada sobre un horario.
     * Si la versión está en PUBLISHED o ARCHIVED, crea automáticamente un nuevo borrador V_next
     * y aplica la modificación exclusivamente sobre él.
     */
    public function createModification(
        ScheduleVersion $version,
        array $data,
        array $uploadedFiles,
        User $actor
    ): array {
        // 1. Validar tenant de la versión
        $this->assertTenantOwnership($version->workPeriod?->company_id, $actor->company_id);

        // 2. Validar motivo obligatorio (mínimo 5 caracteres)
        $reason = trim((string)($data['reason'] ?? ''));
        if (mb_strlen($reason) < 5) {
            throw ValidationException::withMessages([
                'reason' => 'El motivo de la modificación es obligatorio y debe tener al menos 5 caracteres descriptivos.',
            ]);
        }

        // 3. Validar empleado
        $employee = Employee::findOrFail((int)($data['employee_id'] ?? 0));
        $this->assertTenantOwnership($employee->company_id, $actor->company_id);

        // 4. Validar asignación en la versión origen
        $assignmentId = (int)($data['schedule_assignment_id'] ?? 0);
        $sourceAssignment = ScheduleAssignment::where('schedule_version_id', $version->id)->findOrFail($assignmentId);
        if ((int)$sourceAssignment->employee_id !== (int)$employee->id) {
            throw ValidationException::withMessages([
                'schedule_assignment_id' => 'La asignación seleccionada no corresponde al empleado indicado.',
            ]);
        }

        // 5. Validar turno si se utiliza
        if (isset($data['shift_type_id']) && $data['shift_type_id'] !== null) {
            $shiftType = ShiftType::findOrFail((int)$data['shift_type_id']);
            $this->assertTenantOwnership($shiftType->company_id, $actor->company_id);
        }

        // 6. Validar tipo de modificación
        $modTypeRaw = $data['modification_type'] ?? ModificationType::TIME_CHANGE->value;
        $modType = $modTypeRaw instanceof ModificationType ? $modTypeRaw : ModificationType::tryFrom((string)$modTypeRaw);
        if (!$modType) {
            throw ValidationException::withMessages([
                'modification_type' => 'Tipo de modificación no válido.',
            ]);
        }

        // 7. Pre-validar archivos de evidencia
        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile) {
                $this->validateEvidenceFile($file);
            }
        }

        $period = $version->workPeriod;

        // 8. Transacción atómica
        return $period->getConnection()->transaction(function () use (
            $version,
            $period,
            $sourceAssignment,
            $employee,
            $data,
            $uploadedFiles,
            $reason,
            $modType,
            $actor
        ) {
            $conn = $version->getConnectionName();

            // Determinar versión destino
            $isHistorical = in_array($version->status, [ScheduleVersionStatus::PUBLISHED, ScheduleVersionStatus::ARCHIVED], true);

            if ($isHistorical) {
                // Crear nueva versión DRAFT derivada
                $targetVersion = $this->versionService->createDraftFromVersion(
                    $period,
                    $version,
                    "Modificación aplicada: {$reason}",
                    $actor
                );

                // Localizar la asignación equivalente clonada en V_next
                $assignmentDate = $sourceAssignment->getRawOriginal('date') ?? $sourceAssignment->date->format('Y-m-d');
                $targetAssignment = (new ScheduleAssignment())->setConnection($conn)->newQuery()
                    ->where('schedule_version_id', $targetVersion->id)
                    ->where('employee_id', $sourceAssignment->employee_id)
                    ->where('date', $assignmentDate)
                    ->firstOrFail();
            } else {
                $targetVersion = $version;
                $targetAssignment = $sourceAssignment;
            }

            // 9. Capturar snapshot de estado anterior
            $previousData = $this->captureSnapshot($sourceAssignment);

            // 10. Aplicar mutaciones en la asignación de la versión destino
            if (array_key_exists('shift_type_id', $data)) {
                $targetAssignment->shift_type_id = $data['shift_type_id'];
            }
            if (array_key_exists('day_type', $data)) {
                $targetAssignment->day_type = $data['day_type'];
            }
            if (array_key_exists('start_time', $data)) {
                $targetAssignment->start_time = $data['start_time'];
            }
            if (array_key_exists('end_time', $data)) {
                $targetAssignment->end_time = $data['end_time'];
            }
            if (array_key_exists('starts_at', $data)) {
                $targetAssignment->starts_at = $data['starts_at'];
            }
            if (array_key_exists('ends_at', $data)) {
                $targetAssignment->ends_at = $data['ends_at'];
            }
            if (array_key_exists('break_start', $data)) {
                $targetAssignment->break_start = $data['break_start'];
            }
            if (array_key_exists('break_end', $data)) {
                $targetAssignment->break_end = $data['break_end'];
            }
            if (array_key_exists('total_hours', $data)) {
                $targetAssignment->total_hours = (float)$data['total_hours'];
            }
            if (array_key_exists('is_custom', $data)) {
                $targetAssignment->is_custom = (bool)$data['is_custom'];
            }
            if (array_key_exists('notes', $data)) {
                $targetAssignment->notes = $data['notes'];
            }

            $targetAssignment->save();

            // 11. Capturar snapshot de estado nuevo
            $newData = $this->captureSnapshot($targetAssignment->fresh());

            // 12. Crear registro de modificación
            $modification = (new ScheduleModification())->setConnection($conn);
            $modification->fill([
                'schedule_version_id'    => $targetVersion->id,
                'schedule_assignment_id' => $targetAssignment->id,
                'employee_id'            => $employee->id,
                'modification_type'      => $modType,
                'previous_data'          => $previousData,
                'new_data'               => $newData,
                'reason'                 => $reason,
                'created_by'             => $actor->id,
                'approved_by'            => $data['approved_by'] ?? null,
            ]);
            $modification->save();

            // 13. Procesar y almacenar evidencias
            $this->storeEvidenceFiles($modification, $uploadedFiles, $actor, $conn);

            // 14. Revalidar motor de detección de conflictos (Fase 15)
            $conflicts = $this->conflictService->validateVersion($targetVersion, $actor);

            // 15. Auditoría forense
            AuditService::log(
                AuditAction::CREATE,
                ScheduleModification::class,
                $modification->id,
                "Modificación #{$modification->id} ({$modType->value}) registrada por '{$actor->username}' para el empleado {$employee->first_name} {$employee->last_name}" . ($isHistorical ? " generando nueva versión V{$targetVersion->version_number}" : ""),
                null,
                [
                    'modification_type'   => $modType->value,
                    'reason'              => $reason,
                    'schedule_version_id' => $targetVersion->id,
                    'employee_id'         => $employee->id,
                    'created_new_version' => $isHistorical,
                ],
                $period->company_id,
                $conn
            );

            return [
                'modification'      => $modification->fresh(['evidences', 'employee', 'creator', 'version']),
                'resulting_version' => $targetVersion->fresh(['creator', 'parentVersion']),
                'conflicts'         => $conflicts,
                'created_version'   => $isHistorical,
            ];
        });
    }

    /**
     * Lista todas las modificaciones asociadas a una versión de horario.
     */
    public function listModifications(ScheduleVersion $version, User $actor): Collection
    {
        $this->assertTenantOwnership($version->workPeriod?->company_id, $actor->company_id);

        $conn = $version->getConnectionName();

        return (new ScheduleModification())->setConnection($conn)->newQuery()
            ->where('schedule_version_id', $version->id)
            ->with([
                'employee:id,first_name,last_name,employee_code',
                'creator:id,username,email',
                'approver:id,username,email',
                'evidences',
                'assignment.shiftType:id,name,code,color',
            ])
            ->orderBy('id', 'desc')
            ->get();
    }

    /**
     * Obtiene el detalle completo de una modificación.
     */
    public function getModification(int $id, User $actor): ScheduleModification
    {
        $modification = ScheduleModification::with([
            'version.workPeriod',
            'employee',
            'creator:id,username,email',
            'approver:id,username,email',
            'evidences.uploader:id,username,email',
            'assignment.shiftType',
        ])->findOrFail($id);

        $this->assertTenantOwnership($modification->version?->workPeriod?->company_id, $actor->company_id);

        return $modification;
    }

    /**
     * Adjunta una nueva evidencia a una modificación existente.
     */
    public function attachEvidence(
        ScheduleModification $modification,
        UploadedFile $file,
        User $actor
    ): ModificationEvidence {
        $this->assertTenantOwnership($modification->version?->workPeriod?->company_id, $actor->company_id);
        $this->validateEvidenceFile($file);

        $conn = $modification->getConnectionName();
        $companyId = $modification->version->workPeriod->company_id;

        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
        $fileSize = $file->getSize();
        $sha256 = hash_file('sha256', $file->getRealPath());

        $extension = $file->getClientOriginalExtension();
        $storedFilename = uniqid('ev_', true) . '.' . $extension;
        $relativeDir = "companies/{$companyId}/schedule-modifications/{$modification->id}";
        $storagePath = $file->storeAs($relativeDir, $storedFilename, 'local');

        $evidence = (new ModificationEvidence())->setConnection($conn);
        $evidence->fill([
            'schedule_modification_id' => $modification->id,
            'original_name'            => $originalName,
            'stored_filename'          => $storedFilename,
            'storage_path'             => $storagePath,
            'mime_type'                => $mimeType,
            'file_size_bytes'          => $fileSize,
            'sha256_hash'              => $sha256,
            'uploaded_by'              => $actor->id,
        ]);
        $evidence->save();

        AuditService::log(
            AuditAction::CREATE,
            ModificationEvidence::class,
            $evidence->id,
            "Evidencia '{$originalName}' adjuntada a modificación #{$modification->id} por '{$actor->username}'",
            null,
            ['filename' => $originalName, 'sha256' => $sha256, 'size' => $fileSize],
            $companyId,
            $conn
        );

        return $evidence->fresh(['uploader']);
    }

    /**
     * Descarga segura de un archivo de evidencia probatoria con validación de tenant.
     */
    public function downloadEvidence(ModificationEvidence $evidence, User $actor): array
    {
        $companyId = $evidence->modification?->version?->workPeriod?->company_id;
        $this->assertTenantOwnership($companyId, $actor->company_id);

        if (!Storage::disk('local')->exists($evidence->storage_path)) {
            throw new HttpResponseException(response()->json([
                'status'  => 'error',
                'message' => 'El archivo de evidencia solicitado no se encuentra en el almacenamiento.',
            ], Response::HTTP_NOT_FOUND));
        }

        return [
            'absolute_path' => Storage::disk('local')->path($evidence->storage_path),
            'original_name' => $evidence->original_name,
            'mime_type'     => $evidence->mime_type,
            'size'          => $evidence->file_size_bytes,
        ];
    }

    /**
     * Elimina una evidencia únicamente si la versión vinculada sigue en estado DRAFT.
     */
    public function deleteEvidence(ModificationEvidence $evidence, User $actor): bool
    {
        $version = $evidence->modification?->version;
        $companyId = $version?->workPeriod?->company_id;
        $this->assertTenantOwnership($companyId, $actor->company_id);

        if ($version && $version->status !== ScheduleVersionStatus::DRAFT) {
            throw new DomainException("Violación de inmutabilidad: No se pueden eliminar evidencias asociadas a versiones en estado {$version->status->value}.");
        }

        $conn = $evidence->getConnectionName();
        $storagePath = $evidence->storage_path;
        $evidenceId = $evidence->id;
        $originalName = $evidence->original_name;

        if (Storage::disk('local')->exists($storagePath)) {
            Storage::disk('local')->delete($storagePath);
        }

        $evidence->delete();

        AuditService::log(
            AuditAction::DELETE,
            ModificationEvidence::class,
            $evidenceId,
            "Evidencia '{$originalName}' eliminada de modificación #{$evidence->schedule_modification_id} por '{$actor->username}'",
            ['filename' => $originalName, 'storage_path' => $storagePath],
            null,
            $companyId,
            $conn
        );

        return true;
    }

    /**
     * Valida tipos MIME, extensiones y tamaño de archivos de evidencia.
     */
    public function validateEvidenceFile(UploadedFile $file): void
    {
        if ($file->getSize() > self::MAX_FILE_SIZE_BYTES) {
            throw ValidationException::withMessages([
                'evidences' => "El archivo '{$file->getClientOriginalName()}' excede el límite máximo permitido de 10 MB.",
            ]);
        }

        $extension = strtolower((string)$file->getClientOriginalExtension());
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw ValidationException::withMessages([
                'evidences' => "La extensión '.{$extension}' no está permitida. Formatos aceptados: PDF, PNG, JPG, JPEG.",
            ]);
        }

        $mimeType = strtolower((string)($file->getMimeType() ?: $file->getClientMimeType()));
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw ValidationException::withMessages([
                'evidences' => "El tipo MIME '{$mimeType}' no es válido para evidencias documentales.",
            ]);
        }
    }

    /**
     * Captura un snapshot serializable y determinista del estado de una asignación.
     */
    public function captureSnapshot(ScheduleAssignment $assignment): array
    {
        return [
            'id'            => $assignment->id,
            'employee_id'   => $assignment->employee_id,
            'date'          => $assignment->getRawOriginal('date') ?? $assignment->date->format('Y-m-d'),
            'day_type'      => is_object($assignment->day_type) ? $assignment->day_type->value : (string)$assignment->day_type,
            'shift_type_id' => $assignment->shift_type_id,
            'start_time'    => $assignment->start_time,
            'end_time'      => $assignment->end_time,
            'starts_at'     => $assignment->starts_at ? (is_string($assignment->starts_at) ? $assignment->starts_at : $assignment->starts_at->toIso8601String()) : null,
            'ends_at'       => $assignment->ends_at ? (is_string($assignment->ends_at) ? $assignment->ends_at : $assignment->ends_at->toIso8601String()) : null,
            'break_start'   => $assignment->break_start,
            'break_end'     => $assignment->break_end,
            'total_hours'   => (float)$assignment->total_hours,
            'is_custom'     => (bool)$assignment->is_custom,
            'notes'         => $assignment->notes,
        ];
    }

    /**
     * Procesa y almacena una colección de archivos de evidencia vinculados a la modificación.
     */
    private function storeEvidenceFiles(
        ScheduleModification $modification,
        array $uploadedFiles,
        User $actor,
        string $conn
    ): void {
        $companyId = $modification->version->workPeriod->company_id;

        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $mimeType = $file->getMimeType() ?: $file->getClientMimeType();
            $fileSize = $file->getSize();
            $sha256 = hash_file('sha256', $file->getRealPath());

            $extension = $file->getClientOriginalExtension();
            $storedFilename = uniqid('ev_', true) . '.' . $extension;
            $relativeDir = "companies/{$companyId}/schedule-modifications/{$modification->id}";
            $storagePath = $file->storeAs($relativeDir, $storedFilename, 'local');

            $evidence = (new ModificationEvidence())->setConnection($conn);
            $evidence->fill([
                'schedule_modification_id' => $modification->id,
                'original_name'            => $originalName,
                'stored_filename'          => $storedFilename,
                'storage_path'             => $storagePath,
                'mime_type'                => $mimeType,
                'file_size_bytes'          => $fileSize,
                'sha256_hash'              => $sha256,
                'uploaded_by'              => $actor->id,
            ]);
            $evidence->save();
        }
    }

    /**
     * Garantiza el aislamiento estricto de tenant (TENANT-INVARIANT).
     */
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
