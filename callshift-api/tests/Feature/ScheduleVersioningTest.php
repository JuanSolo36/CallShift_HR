<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Company;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Position;
use App\Models\EmploymentType;
use App\Models\Employee;
use App\Models\ShiftType;
use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\DayType;
use App\Enums\WorkPeriodStatus;
use App\Enums\ScheduleVersionStatus;
use App\Services\Schedule\ScheduleVersionService;
use App\Services\Schedule\ScheduleEditorService;
use App\Services\WorkPeriods\WorkPeriodService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use DomainException;
use Symfony\Component\HttpFoundation\Response;

class ScheduleVersioningTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $adminB;
    protected Department $deptA;
    protected Position $posA;
    protected EmploymentType $empTypeA;
    protected Employee $empA;
    protected ShiftType $shiftMorning;
    protected ShiftType $shiftNight;
    protected ShiftType $shiftEarlyMorning;
    protected WorkPeriod $periodA;
    protected ScheduleVersion $versionA1;

    protected ScheduleVersionService $versionService;
    protected ScheduleEditorService $editorService;
    protected WorkPeriodService $workPeriodService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->versionService = app(ScheduleVersionService::class);
        $this->editorService = app(ScheduleEditorService::class);
        $this->workPeriodService = app(WorkPeriodService::class);

        $this->setupBaseData();
    }

    private function setupBaseData(): void
    {
        $roleAdmin = Role::firstOrCreate(['code' => RoleCode::HR_ADMIN->value], ['name' => 'HR Admin', 'hierarchy_level' => 2]);

        $this->companyA = Company::create([
            'name'       => 'Empresa Alfa',
            'legal_name' => 'Empresa Alfa SAC',
            'tax_id'     => '20100000001',
            'email'      => 'admin@alfa.com',
            'country'    => 'PER',
            'currency'   => 'PEN',
            'timezone'   => 'America/Lima',
            'status'     => 'ACTIVE',
        ]);

        $this->companyB = Company::create([
            'name'       => 'Empresa Beta',
            'legal_name' => 'Empresa Beta SAC',
            'tax_id'     => '20100000002',
            'email'      => 'admin@beta.com',
            'country'    => 'PER',
            'currency'   => 'PEN',
            'timezone'   => 'America/Lima',
            'status'     => 'ACTIVE',
        ]);

        $this->adminA = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $roleAdmin->id,
            'username'   => 'admin_a',
            'email'      => 'admin_a@alfa.com',
            'password'   => bcrypt('password123'),
            'status'     => 'ACTIVE',
        ]);

        $this->adminB = User::create([
            'company_id' => $this->companyB->id,
            'role_id'    => $roleAdmin->id,
            'username'   => 'admin_b',
            'email'      => 'admin_b@beta.com',
            'password'   => bcrypt('password123'),
            'status'     => 'ACTIVE',
        ]);

        $this->deptA = Department::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Operaciones Alfa',
            'code'       => 'OP-A',
            'status'     => 'ACTIVE',
        ]);

        $this->posA = Position::create([
            'company_id'    => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'name'          => 'Operador',
            'code'          => 'OP-A1',
            'status'        => 'ACTIVE',
        ]);

        $this->empTypeA = EmploymentType::create([
            'company_id'            => $this->companyA->id,
            'name'                  => 'Jornada Completa',
            'code'                  => 'FT-48',
            'default_weekly_hours'  => 48.0,
            'status'                => 'ACTIVE',
        ]);

        $this->empA = Employee::create([
            'company_id'         => $this->companyA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empTypeA->id,
            'first_name'         => 'Carlos',
            'last_name'          => 'Mendoza',
            'employee_code'      => 'EMP-A001',
            'document_type'      => 'DNI',
            'document_number'    => '10000001',
            'email'              => 'carlos.mendoza@alfa.com',
            'status'             => 'ACTIVE',
            'hire_date'          => '2025-01-01',
        ]);

        $this->shiftMorning = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Mañana 8h',
            'code'             => 'M08',
            'start_time'       => '08:00:00',
            'end_time'         => '16:00:00',
            'total_work_hours' => 8.0,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftNight = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Noche 8h',
            'code'             => 'N08',
            'start_time'       => '22:00:00',
            'end_time'         => '06:00:00',
            'total_work_hours' => 8.0,
            'crosses_midnight' => true,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftEarlyMorning = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Madrugada 8h',
            'code'             => 'EM08',
            'start_time'       => '05:00:00',
            'end_time'         => '13:00:00',
            'total_work_hours' => 8.0,
            'status'           => 'ACTIVE',
        ]);

        $this->periodA = WorkPeriod::create([
            'company_id'    => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'name'          => 'Semana 35 - 2026',
            'start_date'    => '2026-08-24',
            'end_date'      => '2026-08-30',
            'status'        => WorkPeriodStatus::DRAFT->value,
            'created_by'    => $this->adminA->id,
        ]);

        $this->versionA1 = ScheduleVersion::create([
            'work_period_id' => $this->periodA->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'created_by'     => $this->adminA->id,
            'change_summary' => 'Versión inicial V1',
        ]);

        $this->periodA->update(['current_version_id' => $this->versionA1->id]);
    }

    /** 1. Trigger y Modelo bloquean INSERT de asignaciones en versión PUBLISHED y ARCHIVED */
    public function test_database_trigger_and_model_block_assignment_insert_on_published_and_archived_version(): void
    {
        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $published = $this->versionService->publishVersion($this->versionA1, 1, 'Publicación V1', $this->adminA);

        $this->expectException(DomainException::class);
        ScheduleAssignment::create([
            'schedule_version_id' => $published->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8,
        ]);
    }

    /** 2. Trigger y Modelo bloquean UPDATE de asignaciones en versión PUBLISHED */
    public function test_database_trigger_and_model_block_assignment_update_on_published_and_archived_version(): void
    {
        $assignment = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA1->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8,
        ]);

        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $published = $this->versionService->publishVersion($this->versionA1, 1, 'Publicación V1', $this->adminA);

        // A. Intento vía Eloquent directo
        $freshAssignment = ScheduleAssignment::find($assignment->id);
        try {
            $freshAssignment->update(['total_hours' => 10]);
            $this->fail('Debió ser bloqueado por Eloquent o Trigger');
        } catch (\Throwable $e) {
            $this->assertTrue($e instanceof DomainException || $e instanceof \Illuminate\Database\QueryException);
        }

        // B. Intento vía SQL directo / DB::table (debe ser bloqueado por el TRIGGER de BD)
        try {
            DB::table('schedule_assignments')
                ->where('id', $assignment->id)
                ->update(['total_hours' => 12]);
            $this->fail('El trigger de BD debió bloquear el UPDATE directo');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('Inmutabilidad violada', $e->getMessage());
        }
    }

    /** 3. Trigger bidireccional bloquea transferir asignaciones de DRAFT a PUBLISHED vía SQL directo */
    public function test_database_trigger_blocks_moving_draft_assignment_to_published_or_archived_version(): void
    {
        $assignmentDraft = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA1->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8,
        ]);

        $version2 = ScheduleVersion::create([
            'work_period_id' => $this->periodA->id,
            'version_number' => 2,
            'status'         => ScheduleVersionStatus::REVIEW,
            'lock_version'   => 1,
            'created_by'     => $this->adminA->id,
        ]);

        $publishedV2 = $this->versionService->publishVersion($version2, 1, 'Pub V2', $this->adminA);

        $this->expectException(\Exception::class);
        DB::table('schedule_assignments')
            ->where('id', $assignmentDraft->id)
            ->update(['schedule_version_id' => $publishedV2->id]);
    }

    /** 4. Trigger y Modelo bloquean DELETE de asignaciones en versión PUBLISHED */
    public function test_database_trigger_and_model_block_assignment_delete_on_published_and_archived_version(): void
    {
        $assignment = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA1->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8,
        ]);

        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $published = $this->versionService->publishVersion($this->versionA1, 1, 'Pub V1', $this->adminA);

        // A. Intento vía Eloquent directo
        $freshAssignment = ScheduleAssignment::find($assignment->id);
        try {
            $freshAssignment->delete();
            $this->fail('Debió ser bloqueado por Eloquent o Trigger');
        } catch (\Throwable $e) {
            $this->assertTrue($e instanceof DomainException || $e instanceof \Illuminate\Database\QueryException);
        }

        // B. Intento vía SQL directo / DB::table (debe ser bloqueado por el TRIGGER de BD)
        try {
            DB::table('schedule_assignments')
                ->where('id', $assignment->id)
                ->delete();
            $this->fail('El trigger de BD debió bloquear el DELETE directo');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('Inmutabilidad violada', $e->getMessage());
        }
    }

    /** 5. Trigger bloquea DELETE de versiones PUBLISHED y ARCHIVED */
    public function test_database_trigger_blocks_published_and_archived_version_deletion(): void
    {
        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $published = $this->versionService->publishVersion($this->versionA1, 1, 'Pub V1', $this->adminA);

        $freshPublished = ScheduleVersion::find($published->id);
        try {
            $freshPublished->delete();
            $this->fail('Debió ser bloqueado por Eloquent o Trigger');
        } catch (\Throwable $e) {
            $this->assertTrue($e instanceof DomainException || $e instanceof \Illuminate\Database\QueryException);
        }

        try {
            DB::table('schedule_versions')->where('id', $published->id)->delete();
            $this->fail('El trigger de BD debió bloquear el DELETE de versión');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertStringContainsString('Inmutabilidad violada', $e->getMessage());
        }
    }

    /** 6. Trigger bloquea incondicionalmente UPDATE sobre versión ARCHIVED */
    public function test_database_trigger_blocks_version_update_on_archived_version_unconditionally(): void
    {
        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $publishedV1 = $this->versionService->publishVersion($this->versionA1, 1, 'Pub V1', $this->adminA);

        $draftV2 = $this->versionService->createDraftFromVersion($this->periodA, $publishedV1, 'Draft V2', $this->adminA);
        $reviewV2 = $this->versionService->reviewVersion($draftV2, 1, 'Review V2', $this->adminA);
        $publishedV2 = $this->versionService->publishVersion($reviewV2, 2, 'Pub V2', $this->adminA);

        $archivedV1 = $publishedV1->fresh();
        $this->assertEquals(ScheduleVersionStatus::ARCHIVED, $archivedV1->status);

        $this->expectException(DomainException::class);
        $archivedV1->update(['change_summary' => 'Hacked change summary']);
    }

    /** 7. Trigger bloquea UPDATE en versión PUBLISHED si altera columnas estructurales */
    public function test_database_trigger_blocks_version_update_on_published_version_altering_structural_columns(): void
    {
        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $published = $this->versionService->publishVersion($this->versionA1, 1, 'Pub V1', $this->adminA);

        $this->expectException(DomainException::class);
        $published->update(['version_number' => 99]);
    }

    /** 8. Trigger permite la transición estructuralmente invariante de PUBLISHED a ARCHIVED */
    public function test_database_trigger_permits_structural_invariance_on_published_to_archived_transition(): void
    {
        $this->versionA1->update(['status' => ScheduleVersionStatus::REVIEW]);
        $publishedV1 = $this->versionService->publishVersion($this->versionA1, 1, 'Pub V1', $this->adminA);

        $draftV2 = $this->versionService->createDraftFromVersion($this->periodA, $publishedV1, 'Draft V2', $this->adminA);
        $reviewV2 = $this->versionService->reviewVersion($draftV2, 1, 'Review V2', $this->adminA);
        $publishedV2 = $this->versionService->publishVersion($reviewV2, 2, 'Pub V2', $this->adminA);

        $this->assertEquals(ScheduleVersionStatus::ARCHIVED, $publishedV1->fresh()->status);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $publishedV2->fresh()->status);
    }

    /** 9. Invariante I1 (PUBLISH-OWNER): Ninguna ruta externa de aplicación puede publicar sin pasar por ScheduleVersionService */
    public function test_no_application_path_can_publish_schedule_version_without_schedule_version_service(): void
    {
        $response = $this->actingAs($this->adminA)->postJson("/api/v1/schedule-versions/{$this->versionA1->id}/publish", [
            'lock_version'   => 1,
            'change_summary' => 'Bypass attempt',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);

        $this->assertEquals(ScheduleVersionStatus::DRAFT, $this->versionA1->fresh()->status);
    }

    /** 10. WorkPeriodService delega a ScheduleVersionService y rechaza publicar con versión en DRAFT */
    public function test_work_period_publication_cannot_bypass_schedule_version_service(): void
    {
        $this->expectException(ValidationException::class);
        $this->workPeriodService->changeWorkPeriodStatus(
            $this->periodA,
            WorkPeriodStatus::PUBLISHED->value,
            'Intento de publicar periodo con versión en DRAFT',
            1,
            $this->adminA
        );

        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $this->versionA1->fresh()->status);
    }

    /** 11. Máquina de estados completa: DRAFT -> REVIEW -> DRAFT -> REVIEW -> PUBLISHED */
    public function test_strict_state_machine_draft_to_review_to_published_and_return_to_draft(): void
    {
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $this->versionA1->status);

        $vReview = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo para revisión', $this->adminA);
        $this->assertEquals(ScheduleVersionStatus::REVIEW, $vReview->status);
        $this->assertEquals(2, $vReview->lock_version);

        $vDraft = $this->versionService->returnToDraft($vReview, 2, 'Corregir asignaciones', $this->adminA);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $vDraft->status);
        $this->assertEquals(3, $vDraft->lock_version);

        $vReview2 = $this->versionService->reviewVersion($vDraft, 3, 'Revisión final', $this->adminA);
        $this->assertEquals(ScheduleVersionStatus::REVIEW, $vReview2->status);
        $this->assertEquals(4, $vReview2->lock_version);

        $vPublished = $this->versionService->publishVersion($vReview2, 4, 'Horario Oficial', $this->adminA);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $vPublished->status);
        $this->assertEquals(5, $vPublished->lock_version);
    }

    /** 12. Ciclo returnToDraft y re-review valida lock_version y genera logs de auditoría */
    public function test_return_to_draft_and_re_review_cycle_with_lock_versions_and_audit(): void
    {
        $vReview = $this->versionService->reviewVersion($this->versionA1, 1, 'Review 1', $this->adminA);

        $this->expectException(HttpResponseException::class);
        $this->versionService->returnToDraft($vReview, 1, 'Stale lock', $this->adminA);
    }

    /** 13. Publicar rechaza versiones en DRAFT sin previa revisión */
    public function test_publish_rejects_draft_version_without_prior_review(): void
    {
        $this->expectException(ValidationException::class);
        $this->versionService->publishVersion($this->versionA1, 1, 'Intento directo', $this->adminA);
    }

    /** 14. reviewVersion y returnToDraft exigen e incrementan lock_version y registran auditoría */
    public function test_review_version_and_return_to_draft_require_and_increment_lock_version_and_log_audit(): void
    {
        $this->versionService->reviewVersion($this->versionA1, 1, 'Notas de revisión', $this->adminA);

        $log = AuditLog::where('company_id', $this->companyA->id)
            ->where('auditable_type', ScheduleVersion::class)
            ->where('auditable_id', $this->versionA1->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('enviada a revisión', $log->description);
    }

    /** 15. publishVersion archiva versión publicada anterior y registra VERSION_ARCHIVED y VERSION_PUBLISHED */
    public function test_publish_archives_previous_published_version_and_logs_version_archived_and_version_published(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Ready V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $v2Draft = $this->versionService->createDraftFromVersion($this->periodA, $v1Pub, 'Draft V2', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2Draft, 1, 'Ready V2', $this->adminA);
        $v2Pub = $this->versionService->publishVersion($v2Rev, 2, 'Pub V2', $this->adminA);

        $this->assertEquals(ScheduleVersionStatus::ARCHIVED, $v1Pub->fresh()->status);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $v2Pub->fresh()->status);

        $archiveLog = AuditLog::where('auditable_id', $v1Pub->id)
            ->where('description', 'like', '%archivada automáticamente%')
            ->first();
        $this->assertNotNull($archiveLog);

        $publishLog = AuditLog::where('auditable_id', $v2Pub->id)
            ->where('description', 'like', '%publicada oficialmente%')
            ->first();
        $this->assertNotNull($publishLog);
    }

    /** 16. Invariantes I2 y I3: Max 1 PUBLISHED por periodo y current_version_id sincronizado */
    public function test_publish_enforces_unique_published_and_current_version_invariants(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $v2Draft = $this->versionService->createDraftFromVersion($this->periodA, $v1Pub, 'Draft V2', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2Draft, 1, 'Rev V2', $this->adminA);
        $v2Pub = $this->versionService->publishVersion($v2Rev, 2, 'Pub V2', $this->adminA);

        $publishedCount = ScheduleVersion::where('work_period_id', $this->periodA->id)
            ->where('status', ScheduleVersionStatus::PUBLISHED->value)
            ->count();

        $this->assertEquals(1, $publishedCount);
        $this->assertEquals($v2Pub->id, $this->periodA->fresh()->current_version_id);
    }

    /** 17. Edición de celdas en borrador requiere e incrementa lock_version */
    public function test_draft_mutation_requires_and_increments_lock_version(): void
    {
        $this->editorService->upsertAssignment($this->versionA1, [
            'employee_id'   => $this->empA->id,
            'date'          => '2026-08-24',
            'day_type'      => DayType::WORK->value,
            'shift_type_id' => $this->shiftMorning->id,
            'lock_version'  => 1,
        ], $this->adminA);

        $this->assertEquals(2, $this->versionA1->fresh()->lock_version);
    }

    /** 18. Concurrencia en borrador detecta lock obsoleto y retorna 409 Conflict */
    public function test_concurrent_draft_edit_detects_stale_lock_and_returns_409(): void
    {
        $this->editorService->upsertAssignment($this->versionA1, [
            'employee_id'   => $this->empA->id,
            'date'          => '2026-08-24',
            'day_type'      => DayType::WORK->value,
            'shift_type_id' => $this->shiftMorning->id,
            'lock_version'  => 1,
        ], $this->adminA);

        $this->expectException(HttpResponseException::class);
        $this->editorService->upsertAssignment($this->versionA1, [
            'employee_id'   => $this->empA->id,
            'date'          => '2026-08-25',
            'day_type'      => DayType::WORK->value,
            'shift_type_id' => $this->shiftMorning->id,
            'lock_version'  => 1,
        ], $this->adminA);
    }

    /** 19. Concurrencia real en publicación rechaza lock desactualizado y preserva versión publicada anterior */
    public function test_concurrent_publish_rejects_stale_lock_and_preserves_previous_published_version(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $v2Draft = $this->versionService->createDraftFromVersion($this->periodA, $v1Pub, 'Draft V2', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2Draft, 1, 'Rev V2', $this->adminA);

        $v2Rev->update(['lock_version' => 3]);

        try {
            $this->versionService->publishVersion($v2Rev, 2, 'Pub Concurrente Stale', $this->adminA);
            $this->fail('Debió arrojar 409 Conflict');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_CONFLICT, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $v1Pub->fresh()->status);
        $this->assertEquals(ScheduleVersionStatus::REVIEW, $v2Rev->fresh()->status);
        $this->assertEquals($v1Pub->id, $this->periodA->fresh()->current_version_id);
    }

    /** 20. Invariante I4 (ATOMIC-PUBLISH): Rollback integral de publicación ante fallo */
    public function test_publish_version_rollback_restores_previous_published_and_cleans_audit_logs(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $v2Draft = $this->versionService->createDraftFromVersion($this->periodA, $v1Pub, 'Draft V2', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2Draft, 1, 'Rev V2', $this->adminA);

        // Turno noche 22:00 -> 06:00 día 1
        ScheduleAssignment::create([
            'schedule_version_id' => $v2Rev->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftNight->id,
            'start_time'          => '22:00:00',
            'end_time'            => '06:00:00',
            'total_hours'         => 8,
        ]);
        // Turno madrugada 05:00 -> 13:00 día 2 (solapa con salida de 06:00 -> HARD CONFLICT OVERLAPPING_SHIFTS)
        ScheduleAssignment::create([
            'schedule_version_id' => $v2Rev->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftEarlyMorning->id,
            'start_time'          => '05:00:00',
            'end_time'            => '13:00:00',
            'total_hours'         => 8,
        ]);

        try {
            $this->versionService->publishVersion($v2Rev, 2, 'Pub V2 Fallido', $this->adminA);
            $this->fail('Debió fallar por conflictos HARD');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('conflicts', $e->errors());
        }

        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $v1Pub->fresh()->status);
        $this->assertEquals(ScheduleVersionStatus::REVIEW, $v2Rev->fresh()->status);
        $this->assertEquals($v1Pub->id, $this->periodA->fresh()->current_version_id);
    }

    /** 21. Numeración atómica secuencial bajo creaciones sucesivas */
    public function test_atomic_version_numbering_under_concurrent_creation(): void
    {
        $v2 = $this->versionService->createDraftFromVersion($this->periodA, null, 'Draft 2', $this->adminA);
        $v3 = $this->versionService->createDraftFromVersion($this->periodA, null, 'Draft 3', $this->adminA);
        $v4 = $this->versionService->createDraftFromVersion($this->periodA, null, 'Draft 4', $this->adminA);

        $this->assertEquals(2, $v2->version_number);
        $this->assertEquals(3, $v3->version_number);
        $this->assertEquals(4, $v4->version_number);
    }

    /** 22. createDraftFromVersion valida ownership de periodo y versión origen */
    public function test_create_draft_validates_tenant_and_work_period_ownership(): void
    {
        $periodB = WorkPeriod::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Periodo Beta',
            'start_date' => '2026-08-24',
            'end_date'   => '2026-08-30',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminB->id,
        ]);

        $this->expectException(HttpResponseException::class);
        $this->versionService->createDraftFromVersion($periodB, null, 'Hack', $this->adminA);
    }

    /** 23. Deep-copy clona asignaciones exactas sin arrastrar conflictos históricos */
    public function test_deep_copy_clones_exact_assignments_without_historical_conflicts(): void
    {
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA1->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'start_time'          => '08:00:00',
            'end_time'            => '16:00:00',
            'total_hours'         => 8.0,
            'notes'               => 'Turno inicial',
        ]);

        $v2 = $this->versionService->createDraftFromVersion($this->periodA, $this->versionA1, 'Clonado V2', $this->adminA);

        $this->assertEquals(1, $v2->assignments()->count());
        $cloned = $v2->assignments()->first();
        $this->assertEquals($this->empA->id, $cloned->employee_id);
        $this->assertEquals('2026-08-24', $cloned->getRawOriginal('date'));
        $this->assertEquals(8.0, (float)$cloned->total_hours);
        $this->assertEquals('Turno inicial', $cloned->notes);
        $this->assertNotEquals($this->versionA1->assignments()->first()->id, $cloned->id);
    }

    /** 24. restoreVersion crea nuevo borrador no destructivo y preserva versiones históricas */
    public function test_restore_creates_new_draft_and_preserves_all_historical_versions(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $v2Draft = $this->versionService->createDraftFromVersion($this->periodA, $v1Pub, 'Draft V2', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2Draft, 1, 'Rev V2', $this->adminA);
        $v2Pub = $this->versionService->publishVersion($v2Rev, 2, 'Pub V2', $this->adminA);

        $v3Restored = $this->versionService->restoreVersion($this->periodA, $v1Pub, 'Restauración por auditoría', $this->adminA);

        $this->assertEquals(3, $v3Restored->version_number);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $v3Restored->status);
        $this->assertEquals($v1Pub->id, $v3Restored->parent_version_id);

        $this->assertEquals(ScheduleVersionStatus::ARCHIVED, $v1Pub->fresh()->status);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $v2Pub->fresh()->status);
    }

    /** 25. restoreVersion rechaza versiones destino en DRAFT o REVIEW y versiones de otro periodo */
    public function test_restore_rejects_draft_and_review_targets_and_cross_period_targets(): void
    {
        $this->expectException(ValidationException::class);
        $this->versionService->restoreVersion($this->periodA, $this->versionA1, 'Intento de restaurar DRAFT', $this->adminA);
    }

    /** 26. Rollback atómico de restoreVersion ante fallo */
    public function test_restore_rolls_back_version_creation_when_restore_audit_fails(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        try {
            $this->versionService->restoreVersion($this->periodA, $v1Pub, 'abc', $this->adminA);
            $this->fail('Debió fallar por motivo corto');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }

        $this->assertEquals(1, ScheduleVersion::where('work_period_id', $this->periodA->id)->count());
    }

    /** 27. compareVersions calcula diferencias semánticas precisas y deltas de horas */
    public function test_compare_versions_calculates_accurate_semantic_diff_and_deltas(): void
    {
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA1->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $v2 = $this->versionService->createDraftFromVersion($this->periodA, $this->versionA1, 'V2', $this->adminA);

        ScheduleAssignment::where('schedule_version_id', $v2->id)->update(['total_hours' => 10.0]);
        ScheduleAssignment::create([
            'schedule_version_id' => $v2->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $diff = $this->versionService->compareVersions($this->versionA1, $v2, $this->adminA);

        $this->assertCount(1, $diff['diff']['modified']);
        $this->assertCount(1, $diff['diff']['added']);
        $this->assertCount(0, $diff['diff']['removed']);
        $this->assertEquals(10.0, $diff['diff']['hours_delta']);
    }

    /** 28. TENANT-INVARIANT: reviewVersion rechaza cross-tenant con 0 mutaciones y 0 logs */
    public function test_review_version_rejects_cross_tenant_version(): void
    {
        $logCountBefore = AuditLog::count();

        try {
            $this->versionService->reviewVersion($this->versionA1, 1, 'Ataque cross-tenant', $this->adminB);
            $this->fail('Debió rechazar por cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals(ScheduleVersionStatus::DRAFT, $this->versionA1->fresh()->status);
        $this->assertEquals(1, $this->versionA1->fresh()->lock_version);
        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 29. TENANT-INVARIANT: returnToDraft rechaza cross-tenant con 0 mutaciones y 0 logs */
    public function test_return_to_draft_rejects_cross_tenant_version(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Review A', $this->adminA);
        $logCountBefore = AuditLog::count();

        try {
            $this->versionService->returnToDraft($vRev, 2, 'Ataque cross-tenant', $this->adminB);
            $this->fail('Debió rechazar por cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals(ScheduleVersionStatus::REVIEW, $vRev->fresh()->status);
        $this->assertEquals(2, $vRev->fresh()->lock_version);
        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 30. TENANT-INVARIANT: publishVersion rechaza cross-tenant con 0 mutaciones, 0 auto-archive y 0 logs */
    public function test_publish_version_rejects_cross_tenant_version(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Review A', $this->adminA);
        $logCountBefore = AuditLog::count();

        try {
            $this->versionService->publishVersion($vRev, 2, 'Ataque publish cross-tenant', $this->adminB);
            $this->fail('Debió rechazar por cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals(ScheduleVersionStatus::REVIEW, $vRev->fresh()->status);
        $this->assertEquals(2, $vRev->fresh()->lock_version);
        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);
        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 31. TENANT-INVARIANT: compareVersions rechaza versiones de diferentes tenants */
    public function test_compare_versions_rejects_cross_tenant_versions(): void
    {
        $periodB = WorkPeriod::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Periodo Beta',
            'start_date' => '2026-08-24',
            'end_date'   => '2026-08-30',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminB->id,
        ]);

        $versionB = ScheduleVersion::create([
            'work_period_id' => $periodB->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'created_by'     => $this->adminB->id,
        ]);

        $this->expectException(HttpResponseException::class);
        $this->versionService->compareVersions($this->versionA1, $versionB, $this->adminA);
    }

    /** 32. TENANT-INVARIANT: listVersions rechaza periodos de otra empresa */
    public function test_list_versions_rejects_cross_tenant_period(): void
    {
        $logCountBefore = AuditLog::count();

        try {
            $this->versionService->listVersions($this->periodA, $this->adminB);
            $this->fail('Debió rechazar listVersions por cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 33. TENANT-INVARIANT: getVersion rechaza versiones de otra empresa */
    public function test_get_version_rejects_cross_tenant_version(): void
    {
        $logCountBefore = AuditLog::count();

        try {
            $this->versionService->getVersion($this->versionA1->id, $this->adminB);
            $this->fail('Debió rechazar getVersion por cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 34. TENANT-INVARIANT: createDraftFromVersion rechaza periodo o versión origen de otra empresa */
    public function test_create_draft_rejects_cross_tenant_period_or_source_version(): void
    {
        $logCountBefore = AuditLog::where('auditable_type', ScheduleVersion::class)->count();
        $versionsCountBeforeA = ScheduleVersion::where('work_period_id', $this->periodA->id)->count();

        // Intento 1: Periodo de Tenant A con Actor de Tenant B
        try {
            $this->versionService->createDraftFromVersion($this->periodA, null, 'Intento Cross-Tenant', $this->adminB);
            $this->fail('Debió rechazar createDraftFromVersion por periodo cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        // Intento 2: Periodo de Tenant B usando Version de Tenant A como source_version
        $periodB = WorkPeriod::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Periodo Beta B',
            'start_date' => '2026-08-24',
            'end_date'   => '2026-08-30',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminB->id,
        ]);

        try {
            $this->versionService->createDraftFromVersion($periodB, $this->versionA1, 'Intento Source Cross-Tenant', $this->adminB);
            $this->fail('Debió rechazar createDraftFromVersion por source_version cross-tenant/period');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('source_version_id', $e->errors());
        }

        // Verificaciones destructivas de cero alteraciones
        $this->assertEquals($versionsCountBeforeA, ScheduleVersion::where('work_period_id', $this->periodA->id)->count());
        $this->assertEquals(0, ScheduleVersion::where('work_period_id', $periodB->id)->count());
        $this->assertEquals($logCountBefore, AuditLog::where('auditable_type', ScheduleVersion::class)->count());
    }

    /** 35. TENANT-INVARIANT: restoreVersion rechaza periodo o versión objetivo de otra empresa */
    public function test_restore_version_rejects_cross_tenant_period_or_target_version(): void
    {
        // Publicar V1 formalmente en Periodo A
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $logCountBefore = AuditLog::where('auditable_type', ScheduleVersion::class)->count();
        $versionsCountBeforeA = ScheduleVersion::where('work_period_id', $this->periodA->id)->count();

        // Intento 1: Periodo A + Target V1Pub con Actor de Tenant B
        try {
            $this->versionService->restoreVersion($this->periodA, $v1Pub, 'Restaurar Ataque', $this->adminB);
            $this->fail('Debió rechazar restoreVersion por periodo cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        // Intento 2: Periodo B con Target V1Pub de Tenant A con Actor de Tenant B
        $periodB = WorkPeriod::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Periodo Beta B',
            'start_date' => '2026-08-24',
            'end_date'   => '2026-08-30',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminB->id,
        ]);

        try {
            $this->versionService->restoreVersion($periodB, $v1Pub, 'Restaurar Cross Period', $this->adminB);
            $this->fail('Debió rechazar restoreVersion por target_version de otro periodo/tenant');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('target_version_id', $e->errors());
        }

        // Verificaciones destructivas
        $this->assertEquals($versionsCountBeforeA, ScheduleVersion::where('work_period_id', $this->periodA->id)->count());
        $this->assertEquals(0, ScheduleVersion::where('work_period_id', $periodB->id)->count());
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $v1Pub->fresh()->status);
        $this->assertEquals($logCountBefore, AuditLog::where('auditable_type', ScheduleVersion::class)->count());
    }

    /** 36. TENANT-INVARIANT: Endpoints HTTP de versionamiento rechazan accesos cross-tenant con 403/404 y 0 mutaciones */
    public function test_http_endpoints_enforce_tenant_isolation_with_403_and_zero_mutations(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->adminB);

        $logCountBefore = AuditLog::count();
        $versionsCountBefore = ScheduleVersion::count();
        $assignmentsCountBefore = ScheduleAssignment::count();

        // 1. List
        $this->getJson("/api/v1/work-periods/{$this->periodA->id}/versions")->assertStatus(404);

        // 2. Show
        $this->getJson("/api/v1/schedule-versions/{$this->versionA1->id}")->assertStatus(403);

        // 3. Store
        $this->postJson("/api/v1/work-periods/{$this->periodA->id}/versions", [
            'change_summary' => 'Ataque HTTP',
        ])->assertStatus(404);

        // 4. Review (PATCH)
        $this->patchJson("/api/v1/schedule-versions/{$this->versionA1->id}/review", [
            'lock_version' => 1,
            'notes'        => 'Ataque HTTP',
        ])->assertStatus(403);

        // 5. Return to draft (PATCH)
        $this->patchJson("/api/v1/schedule-versions/{$this->versionA1->id}/return-to-draft", [
            'lock_version' => 1,
            'reason'       => 'Ataque HTTP',
        ])->assertStatus(403);

        // 6. Publish (POST)
        $this->postJson("/api/v1/schedule-versions/{$this->versionA1->id}/publish", [
            'lock_version' => 1,
        ])->assertStatus(403);

        // 7. Restore (POST)
        $this->postJson("/api/v1/work-periods/{$this->periodA->id}/versions/restore", [
            'target_version_id' => $this->versionA1->id,
            'reason'            => 'Ataque HTTP Restore',
        ])->assertStatus(404);

        // 8. Compare (GET)
        $this->getJson("/api/v1/schedule-versions/{$this->versionA1->id}/compare/{$this->versionA1->id}")->assertStatus(403);

        // Verificaciones destructivas
        $this->assertEquals($versionsCountBefore, ScheduleVersion::count());
        $this->assertEquals($assignmentsCountBefore, ScheduleAssignment::count());
        $this->assertEquals($logCountBefore, AuditLog::count());
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $this->versionA1->fresh()->status);
        $this->assertEquals(1, $this->versionA1->fresh()->lock_version);
    }
}
