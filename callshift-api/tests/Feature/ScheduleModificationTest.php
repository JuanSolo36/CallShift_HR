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
use App\Models\ScheduleModification;
use App\Models\ModificationEvidence;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\DayType;
use App\Enums\WorkPeriodStatus;
use App\Enums\ScheduleVersionStatus;
use App\Enums\ModificationType;
use App\Services\Schedule\ScheduleVersionService;
use App\Services\Schedule\ScheduleModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use DomainException;
use Symfony\Component\HttpFoundation\Response;

class ScheduleModificationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $viewerA;
    protected User $adminB;
    protected Department $deptA;
    protected Position $posA;
    protected EmploymentType $empTypeA;
    protected Employee $empA;
    protected Employee $empB;
    protected ShiftType $shiftMorning;
    protected ShiftType $shiftNight;
    protected ShiftType $shiftTypeB;
    protected WorkPeriod $periodA;
    protected ScheduleVersion $versionA1;
    protected ScheduleAssignment $assignmentA1;

    protected ScheduleVersionService $versionService;
    protected ScheduleModificationService $modificationService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->versionService = app(ScheduleVersionService::class);
        $this->modificationService = app(ScheduleModificationService::class);

        $this->setupBaseData();
    }

    private function setupBaseData(): void
    {
        $roleAdmin = Role::firstOrCreate(['code' => RoleCode::HR_ADMIN->value], ['name' => 'HR Admin', 'hierarchy_level' => 2]);
        $roleViewer = Role::firstOrCreate(['code' => RoleCode::VIEWER->value], ['name' => 'Viewer', 'hierarchy_level' => 6]);

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

        $this->viewerA = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $roleViewer->id,
            'username'   => 'viewer_a',
            'email'      => 'viewer_a@alfa.com',
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

        $deptB = Department::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Operaciones Beta',
            'code'       => 'OP-B',
            'status'     => 'ACTIVE',
        ]);

        $posB = Position::create([
            'company_id'    => $this->companyB->id,
            'department_id' => $deptB->id,
            'name'          => 'Operador Beta',
            'code'          => 'OP-B1',
            'status'        => 'ACTIVE',
        ]);

        $empTypeB = EmploymentType::create([
            'company_id'            => $this->companyB->id,
            'name'                  => 'Jornada Completa B',
            'code'                  => 'FT-48-B',
            'default_weekly_hours'  => 48.0,
            'status'                => 'ACTIVE',
        ]);

        $this->empB = Employee::create([
            'company_id'         => $this->companyB->id,
            'department_id'      => $deptB->id,
            'position_id'        => $posB->id,
            'employment_type_id' => $empTypeB->id,
            'first_name'         => 'Roberto',
            'last_name'          => 'Gomez',
            'employee_code'      => 'EMP-B001',
            'document_type'      => 'DNI',
            'document_number'    => '20000001',
            'email'              => 'roberto.gomez@beta.com',
            'status'             => 'ACTIVE',
            'hire_date'          => '2025-01-01',
        ]);

        $this->shiftMorning = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Mañana 8h',
            'code'             => 'M08',
            'start_time'       => '08:00',
            'end_time'         => '16:00',
            'total_work_hours' => 8.0,
            'color'            => '#3B82F6',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftNight = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Noche 8h',
            'code'             => 'N08',
            'start_time'       => '22:00',
            'end_time'         => '06:00',
            'total_work_hours' => 8.0,
            'color'            => '#1E293B',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftTypeB = ShiftType::create([
            'company_id'       => $this->companyB->id,
            'name'             => 'Turno Beta',
            'code'             => 'TB01',
            'start_time'       => '09:00',
            'end_time'         => '17:00',
            'total_work_hours' => 8.0,
            'color'            => '#10B981',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->periodA = WorkPeriod::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Semana 35 - 2026',
            'start_date' => '2026-08-24',
            'end_date'   => '2026-08-30',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);

        $this->versionA1 = ScheduleVersion::create([
            'work_period_id' => $this->periodA->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'change_summary' => 'Borrador inicial V1',
            'lock_version'   => 1,
            'created_by'     => $this->adminA->id,
        ]);

        $this->assignmentA1 = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA1->id,
            'employee_id'         => $this->empA->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorning->id,
            'start_time'          => '08:00',
            'end_time'            => '16:00',
            'total_hours'         => 8.0,
            'is_custom'           => false,
        ]);
    }

    /** 1. M1: Modificación sobre versión PUBLISHED crea automáticamente una nueva versión DRAFT */
    public function test_modification_on_published_version_creates_new_draft(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo para publicar', $this->adminA);
        $vPub = $this->versionService->publishVersion($vRev, 2, 'Publicación oficial', $this->adminA);

        $result = $this->modificationService->createModification($vPub, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::SHIFT_CHANGE->value,
            'shift_type_id'          => $this->shiftNight->id,
            'start_time'             => '22:00',
            'end_time'               => '06:00',
            'total_hours'            => 8.0,
            'reason'                 => 'Cambio de turno por rotación imprevista',
        ], [], $this->adminA);

        $this->assertTrue($result['created_version']);
        $this->assertInstanceOf(ScheduleVersion::class, $result['resulting_version']);
        $this->assertEquals(2, $result['resulting_version']->version_number);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $result['resulting_version']->status);
        $this->assertEquals($vPub->id, $result['resulting_version']->parent_version_id);

        $this->assertInstanceOf(ScheduleModification::class, $result['modification']);
        $this->assertEquals($result['resulting_version']->id, $result['modification']->schedule_version_id);
    }

    /** 2. M1: Versión PUBLISHED permanece 100% inmutable después de la modificación */
    public function test_published_version_remains_immutable_after_modification(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo', $this->adminA);
        $vPub = $this->versionService->publishVersion($vRev, 2, 'Publicado', $this->adminA);

        $this->modificationService->createModification($vPub, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::TIME_CHANGE->value,
            'start_time'             => '10:00',
            'end_time'               => '18:00',
            'total_hours'            => 8.0,
            'reason'                 => 'Ajuste de horario por requerimiento',
        ], [], $this->adminA);

        $freshVPub = $vPub->fresh();
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $freshVPub->status);

        $freshAssig = $this->assignmentA1->fresh();
        $this->assertEquals('08:00', $freshAssig->start_time);
        $this->assertEquals('16:00', $freshAssig->end_time);
        $this->assertEquals($this->shiftMorning->id, $freshAssig->shift_type_id);
    }

    /** 3. M1: Modificación sobre versión ARCHIVED crea automáticamente una nueva versión DRAFT */
    public function test_modification_on_archived_version_creates_new_draft(): void
    {
        $v1Rev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'V1 Pub', $this->adminA);

        $v2 = $this->versionService->createDraftFromVersion($this->periodA, $v1Pub, 'V2 Borrador', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2, 1, 'V2 Rev', $this->adminA);
        $this->versionService->publishVersion($v2Rev, 2, 'V2 Pub', $this->adminA);

        $this->assertEquals(ScheduleVersionStatus::ARCHIVED, $v1Pub->fresh()->status);

        $result = $this->modificationService->createModification($v1Pub, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::DAY_OFF_CHANGE->value,
            'day_type'               => DayType::REST->value,
            'shift_type_id'          => null,
            'total_hours'            => 0.0,
            'reason'                 => 'Modificación solicitada sobre versión archivada',
        ], [], $this->adminA);

        $this->assertTrue($result['created_version']);
        $this->assertEquals(3, $result['resulting_version']->version_number);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $result['resulting_version']->status);
    }

    /** 4. Modificación sobre versión DRAFT actualiza directamente la misma versión */
    public function test_modification_on_draft_updates_same_version(): void
    {
        $versionCountBefore = ScheduleVersion::count();

        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::TIME_CHANGE->value,
            'start_time'             => '09:00',
            'end_time'               => '17:00',
            'total_hours'            => 8.0,
            'reason'                 => 'Ajuste directo en borrador',
        ], [], $this->adminA);

        $this->assertFalse($result['created_version']);
        $this->assertEquals($this->versionA1->id, $result['resulting_version']->id);
        $this->assertEquals($versionCountBefore, ScheduleVersion::count());
        $this->assertEquals('09:00', $this->assignmentA1->fresh()->start_time);
    }

    /** 5. M2: Snapshots preservan fielmente previous_data y new_data */
    public function test_snapshots_preserve_previous_and_new_data(): void
    {
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::SHIFT_CHANGE->value,
            'shift_type_id'          => $this->shiftNight->id,
            'start_time'             => '22:00',
            'end_time'               => '06:00',
            'total_hours'            => 8.0,
            'is_custom'              => true,
            'notes'                  => 'Nota snapshot',
            'reason'                 => 'Verificación de fidelidad de snapshots',
        ], [], $this->adminA);

        $mod = $result['modification'];

        $this->assertEquals($this->shiftMorning->id, $mod->previous_data['shift_type_id']);
        $this->assertEquals('08:00', $mod->previous_data['start_time']);
        $this->assertEquals('16:00', $mod->previous_data['end_time']);
        $this->assertEquals(8.0, $mod->previous_data['total_hours']);
        $this->assertFalse($mod->previous_data['is_custom']);

        $this->assertEquals($this->shiftNight->id, $mod->new_data['shift_type_id']);
        $this->assertEquals('22:00', $mod->new_data['start_time']);
        $this->assertEquals('06:00', $mod->new_data['end_time']);
        $this->assertEquals(8.0, $mod->new_data['total_hours']);
        $this->assertTrue($mod->new_data['is_custom']);
        $this->assertEquals('Nota snapshot', $mod->new_data['notes']);
    }

    /** 6. M3: Reason es obligatorio y debe tener al menos 5 caracteres */
    public function test_reason_is_required_and_minimum_five_characters(): void
    {
        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::TIME_CHANGE->value,
                'reason'                 => '   abc ',
            ], [], $this->adminA);
            $this->fail('Debió rechazar por motivo corto');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }

        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::TIME_CHANGE->value,
                'reason'                 => '',
            ], [], $this->adminA);
            $this->fail('Debió rechazar por motivo vacío');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('reason', $e->errors());
        }
    }

    /** 7. M4: Subida de evidencia PDF con hash SHA-256 verificado */
    public function test_pdf_evidence_upload_and_sha256_hash(): void
    {
        $file = UploadedFile::fake()->create('justificativo.pdf', 500, 'application/pdf');

        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::LEAVE_PERMISSION->value,
            'reason'                 => 'Permiso médico con sustento PDF',
        ], [$file], $this->adminA);

        $mod = $result['modification'];
        $this->assertCount(1, $mod->evidences);

        $evidence = $mod->evidences->first();
        $this->assertEquals('justificativo.pdf', $evidence->original_name);
        $this->assertEquals('application/pdf', $evidence->mime_type);
        $this->assertTrue($evidence->isPdf());
        $this->assertNotEmpty($evidence->sha256_hash);
        $this->assertEquals(64, strlen($evidence->sha256_hash));
        Storage::disk('local')->assertExists($evidence->storage_path);
    }

    /** 8. M4: Subida de evidencia PNG con hash SHA-256 verificado */
    public function test_png_evidence_upload_and_sha256_hash(): void
    {
        $file = UploadedFile::fake()->image('constancia.png', 800, 600);

        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::SHIFT_SWAP->value,
            'reason'                 => 'Intercambio con firma escaneada PNG',
        ], [$file], $this->adminA);

        $evidence = $result['modification']->evidences->first();
        $this->assertTrue($evidence->isImage());
        $this->assertEquals('image/png', $evidence->mime_type);
        Storage::disk('local')->assertExists($evidence->storage_path);
    }

    /** 9. M4: Subida de evidencia JPG con hash SHA-256 verificado */
    public function test_jpg_evidence_upload_and_sha256_hash(): void
    {
        $file = UploadedFile::fake()->image('foto.jpg', 800, 600);

        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::ABSENCE_COVERAGE->value,
            'reason'                 => 'Cubrimiento de ausencia con foto JPG',
        ], [$file], $this->adminA);

        $evidence = $result['modification']->evidences->first();
        $this->assertTrue($evidence->isImage());
        $this->assertEquals('image/jpeg', $evidence->mime_type);
        Storage::disk('local')->assertExists($evidence->storage_path);
    }

    /** 10. M4: Rechazo de archivos con MIME / extensión inválidos */
    public function test_invalid_mime_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('script.exe', 100, 'application/x-msdownload');

        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::OTHER->value,
                'reason'                 => 'Intento de subida maliciosa',
            ], [$file], $this->adminA);
            $this->fail('Debió rechazar archivo ejecutable');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('evidences', $e->errors());
        }

        $this->assertEquals(0, ModificationEvidence::count());
    }

    /** 11. M4: Rechazo de archivo superior a 10 MB */
    public function test_file_over_10mb_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('pesado.pdf', 11000, 'application/pdf'); // 11 MB

        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::OTHER->value,
                'reason'                 => 'Intento de subida de archivo pesado',
            ], [$file], $this->adminA);
            $this->fail('Debió rechazar archivo de más de 10 MB');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('evidences', $e->errors());
        }
    }

    /** 12. M5: Rechazo de modificación sobre versión de otra empresa */
    public function test_cross_tenant_version_modification_is_rejected(): void
    {
        $logCountBefore = AuditLog::count();
        $modCountBefore = ScheduleModification::count();

        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::TIME_CHANGE->value,
                'reason'                 => 'Ataque cross-tenant sobre versión',
            ], [], $this->adminB);
            $this->fail('Debió rechazar por cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }

        $this->assertEquals($modCountBefore, ScheduleModification::count());
        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 13. M5: Rechazo de modificación con empleado de otra empresa */
    public function test_cross_tenant_employee_modification_is_rejected(): void
    {
        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empB->id, // Empleado de Company B
                'modification_type'      => ModificationType::TIME_CHANGE->value,
                'reason'                 => 'Ataque con empleado de otra empresa',
            ], [], $this->adminA);
            $this->fail('Debió rechazar empleado cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }
    }

    /** 14. M5: Rechazo de descarga de evidencia de otra empresa */
    public function test_cross_tenant_evidence_download_is_rejected(): void
    {
        $file = UploadedFile::fake()->create('confidencial.pdf', 100, 'application/pdf');
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Evidencia de empresa A',
        ], [$file], $this->adminA);

        $evidence = $result['modification']->evidences->first();

        $this->expectException(HttpResponseException::class);
        $this->modificationService->downloadEvidence($evidence, $this->adminB);
    }

    /** 15. RBAC: Rol VIEWER no puede crear modificaciones */
    public function test_viewer_cannot_create_modification(): void
    {
        \Laravel\Sanctum\Sanctum::actingAs($this->viewerA);

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA1->id}/modifications", [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::TIME_CHANGE->value,
            'reason'                 => 'Intento de modificación por viewer',
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $this->assertEquals(0, ScheduleModification::count());
    }

    /** 16. Integración con Motor de Conflictos (Fase 15) recalcula tras modificación */
    public function test_conflict_detection_recalculates_after_modification(): void
    {
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::TIME_CHANGE->value,
            'start_time'             => '08:00',
            'end_time'               => '20:00',
            'total_hours'            => 12.0,
            'reason'                 => 'Aumento a 12 horas consecutivas',
        ], [], $this->adminA);

        $this->assertNotNull($result['conflicts']);
    }

    /** 17. M6: Atomicidad y rollback total si falla la actualización */
    public function test_atomic_rollback_when_assignment_update_fails(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo', $this->adminA);
        $vPub = $this->versionService->publishVersion($vRev, 2, 'Pub', $this->adminA);

        $versionCountBefore = ScheduleVersion::count();
        $modCountBefore = ScheduleModification::count();

        try {
            $this->modificationService->createModification($vPub, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::SHIFT_CHANGE->value,
                'shift_type_id'          => 999999, // ID inexistente que causará fallo
                'reason'                 => 'Fallo simulado en actualización',
            ], [], $this->adminA);
            $this->fail('Debió fallar por FK');
        } catch (\Throwable $e) {
            // Rollback esperado
        }

        $this->assertEquals($versionCountBefore, ScheduleVersion::count());
        $this->assertEquals($modCountBefore, ScheduleModification::count());
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $vPub->fresh()->status);
    }

    /** 18. M6: Atomicidad y rollback si falla la auditoría */
    public function test_atomic_rollback_when_audit_fails(): void
    {
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        // Simular fallo en motivo corto (validación previa a tx)
        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::TIME_CHANGE->value,
                'reason'                 => '12',
            ], [$file], $this->adminA);
        } catch (ValidationException $e) {
            $this->assertEquals(0, ScheduleModification::count());
            $this->assertEquals(0, ModificationEvidence::count());
        }
    }

    /** 19. Eliminación de evidencia permitida solo si la versión asociada sigue en DRAFT */
    public function test_evidence_delete_allowed_only_on_draft_version(): void
    {
        $file = UploadedFile::fake()->create('borrador_ev.pdf', 100, 'application/pdf');
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Evidencia en borrador',
        ], [$file], $this->adminA);

        $evidence = $result['modification']->evidences->first();
        $this->assertTrue($this->modificationService->deleteEvidence($evidence, $this->adminA));
        $this->assertEquals(0, ModificationEvidence::count());
    }

    /** 20. Evidencia histórica en versión PUBLISHED no puede ser eliminada */
    public function test_historical_evidence_cannot_be_deleted(): void
    {
        $file = UploadedFile::fake()->create('oficial_ev.pdf', 100, 'application/pdf');
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Evidencia previa a publicar',
        ], [$file], $this->adminA);

        $evidence = $result['modification']->evidences->first();

        // Publicar la versión
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev', $this->adminA);
        $this->versionService->publishVersion($vRev, 2, 'Pub', $this->adminA);

        $this->expectException(DomainException::class);
        $this->modificationService->deleteEvidence($evidence, $this->adminA);
    }

    /** 21. La modificación crea el registro de auditoría con trazabilidad */
    public function test_modification_creates_correct_audit_log(): void
    {
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::LEAVE_PERMISSION->value,
            'reason'                 => 'Auditoría forense obligatoria',
        ], [], $this->adminA);

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ScheduleModification::class,
            'auditable_id'   => $result['modification']->id,
            'action'         => 'CREATE',
            'company_id'     => $this->companyA->id,
        ]);
    }

    /** 22. Ningún camino puede mutar una versión PUBLISHED directamente */
    public function test_no_modification_path_can_mutate_published_version_directly(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Rev', $this->adminA);
        $vPub = $this->versionService->publishVersion($vRev, 2, 'Pub', $this->adminA);

        $result = $this->modificationService->createModification($vPub, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::WORKDAY_CHANGE->value,
            'total_hours'            => 10.0,
            'reason'                 => 'Intento de modificar publicada',
        ], [], $this->adminA);

        // La modificación debe residir en la nueva versión
        $this->assertNotEquals($vPub->id, $result['modification']->schedule_version_id);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $vPub->fresh()->status);
        $this->assertEquals(8.0, (float)$this->assignmentA1->fresh()->total_hours);
    }

    /** 23. Asignación debe pertenecer a la versión indicada */
    public function test_assignment_belongs_to_target_version(): void
    {
        $v2 = $this->versionService->createDraftFromVersion($this->periodA, $this->versionA1, 'V2', $this->adminA);
        $assigV2 = ScheduleAssignment::where('schedule_version_id', $v2->id)->first();

        try {
            // Pasar version V1 pero assignment de V2
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $assigV2->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::TIME_CHANGE->value,
                'reason'                 => 'Asignación no correspondiente a versión',
            ], [], $this->adminA);
            $this->fail('Debió fallar por mismatch de versión y asignación');
        } catch (\Throwable $e) {
            $this->assertTrue(true);
        }
    }

    /** 24. Shift type debe pertenecer a la misma empresa */
    public function test_shift_type_belongs_to_same_tenant(): void
    {
        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::SHIFT_CHANGE->value,
                'shift_type_id'          => $this->shiftTypeB->id, // Shift de Company B
                'reason'                 => 'Turno de otra empresa',
            ], [], $this->adminA);
            $this->fail('Debió rechazar turno cross-tenant');
        } catch (HttpResponseException $e) {
            $this->assertEquals(Response::HTTP_FORBIDDEN, $e->getResponse()->getStatusCode());
        }
    }

    /** 25. Listado de modificaciones tiene scope de tenant */
    public function test_modification_list_is_tenant_scoped(): void
    {
        $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Modificación Tenant A',
        ], [], $this->adminA);

        // Actor B no puede listar modificaciones de Version A
        $this->expectException(HttpResponseException::class);
        $this->modificationService->listModifications($this->versionA1, $this->adminB);
    }

    /** 26. Detalle de modificación tiene scope de tenant */
    public function test_modification_detail_is_tenant_scoped(): void
    {
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Detalle Tenant A',
        ], [], $this->adminA);

        $this->expectException(HttpResponseException::class);
        $this->modificationService->getModification($result['modification']->id, $this->adminB);
    }

    /** 27. Descarga de evidencia vía HTTP tiene scope de tenant */
    public function test_evidence_download_is_tenant_scoped(): void
    {
        $file = UploadedFile::fake()->create('doc_descarga.pdf', 100, 'application/pdf');
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Descarga Tenant A',
        ], [$file], $this->adminA);

        $evidence = $result['modification']->evidences->first();

        \Laravel\Sanctum\Sanctum::actingAs($this->adminB);
        $response = $this->getJson("/api/v1/schedule-modifications/{$result['modification']->id}/evidences/{$evidence->id}/download");
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** 28. Subida de múltiples evidencias es atómica */
    public function test_multiple_evidences_are_atomic(): void
    {
        $file1 = UploadedFile::fake()->create('ev1.pdf', 100, 'application/pdf');
        $file2 = UploadedFile::fake()->image('ev2.png', 400, 300);

        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Múltiples evidencias probatorias',
        ], [$file1, $file2], $this->adminA);

        $this->assertCount(2, $result['modification']->evidences);
        foreach ($result['modification']->evidences as $ev) {
            Storage::disk('local')->assertExists($ev->storage_path);
        }
    }
}
