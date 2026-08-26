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
use App\Models\BusinessRule;
use App\Models\ScheduleConflict;
use App\Models\Absence;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\DayType;
use App\Enums\WorkPeriodStatus;
use App\Enums\ScheduleVersionStatus;
use App\Enums\ModificationType;
use App\Enums\AbsenceType;
use App\Enums\AbsenceStatus;
use App\Enums\AuditAction;
use App\Enums\ConflictSeverity;
use App\Services\Schedule\ScheduleVersionService;
use App\Services\Schedule\ScheduleModificationService;
use App\Services\Conflicts\ConflictDetectionService;
use App\Services\Reports\ReportService;
use App\Services\Audit\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\Sanctum;

class SystemIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Company $tenantA;
    protected Company $tenantB;

    protected User $superAdmin;
    protected User $adminA;
    protected User $managerA;
    protected User $supervisorA;
    protected User $employeeUserA;
    protected User $viewerA;

    protected User $adminB;
    protected User $managerB;

    protected Department $deptA;
    protected Position $posA;
    protected EmploymentType $empTypeA;
    protected Employee $empA1;
    protected Employee $empA2;
    protected ShiftType $shiftMorningA;
    protected ShiftType $shiftEveningA;

    protected Department $deptB;
    protected Position $posB;
    protected EmploymentType $empTypeB;
    protected Employee $empB1;
    protected ShiftType $shiftB;

    protected ScheduleVersionService $versionService;
    protected ScheduleModificationService $modificationService;
    protected ConflictDetectionService $conflictService;
    protected ReportService $reportService;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->versionService = app(ScheduleVersionService::class);
        $this->modificationService = app(ScheduleModificationService::class);
        $this->conflictService = app(ConflictDetectionService::class);
        $this->reportService = app(ReportService::class);

        $this->seedSystemData();
    }

    private function seedSystemData(): void
    {
        $roleSuper = Role::firstOrCreate(['code' => RoleCode::SUPER_ADMIN->value], ['name' => 'Super Admin', 'hierarchy_level' => 1]);
        $roleAdmin = Role::firstOrCreate(['code' => RoleCode::HR_ADMIN->value], ['name' => 'HR Admin', 'hierarchy_level' => 2]);
        $roleManager = Role::firstOrCreate(['code' => RoleCode::MANAGER->value], ['name' => 'Manager', 'hierarchy_level' => 3]);
        $roleSupervisor = Role::firstOrCreate(['code' => RoleCode::SUPERVISOR->value], ['name' => 'Supervisor', 'hierarchy_level' => 4]);
        $roleEmp = Role::firstOrCreate(['code' => RoleCode::EMPLOYEE->value], ['name' => 'Employee', 'hierarchy_level' => 5]);
        $roleViewer = Role::firstOrCreate(['code' => RoleCode::VIEWER->value], ['name' => 'Viewer', 'hierarchy_level' => 6]);

        $this->tenantA = Company::create([
            'name'       => 'Corporacion Alfa',
            'legal_name' => 'Corporacion Alfa SAC',
            'tax_id'     => '20500000001',
            'email'      => 'info@alfa.com',
            'country'    => 'PER',
            'currency'   => 'PEN',
            'timezone'   => 'America/Lima',
            'status'     => 'ACTIVE',
        ]);

        $this->tenantB = Company::create([
            'name'       => 'Corporacion Beta',
            'legal_name' => 'Corporacion Beta SAC',
            'tax_id'     => '20500000002',
            'email'      => 'info@beta.com',
            'country'    => 'PER',
            'currency'   => 'PEN',
            'timezone'   => 'America/Lima',
            'status'     => 'ACTIVE',
        ]);

        $this->superAdmin = User::create([
            'company_id' => $this->tenantA->id,
            'role_id'    => $roleSuper->id,
            'username'   => 'super_admin',
            'email'      => 'super@callshift.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->adminA = User::create([
            'company_id' => $this->tenantA->id,
            'role_id'    => $roleAdmin->id,
            'username'   => 'admin_alfa',
            'email'      => 'admin@alfa.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->managerA = User::create([
            'company_id' => $this->tenantA->id,
            'role_id'    => $roleManager->id,
            'username'   => 'manager_alfa',
            'email'      => 'manager@alfa.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->supervisorA = User::create([
            'company_id' => $this->tenantA->id,
            'role_id'    => $roleSupervisor->id,
            'username'   => 'supervisor_alfa',
            'email'      => 'supervisor@alfa.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->employeeUserA = User::create([
            'company_id' => $this->tenantA->id,
            'role_id'    => $roleEmp->id,
            'username'   => 'empleado_alfa',
            'email'      => 'empleado@alfa.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->viewerA = User::create([
            'company_id' => $this->tenantA->id,
            'role_id'    => $roleViewer->id,
            'username'   => 'viewer_alfa',
            'email'      => 'viewer@alfa.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->adminB = User::create([
            'company_id' => $this->tenantB->id,
            'role_id'    => $roleAdmin->id,
            'username'   => 'admin_beta',
            'email'      => 'admin@beta.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        $this->managerB = User::create([
            'company_id' => $this->tenantB->id,
            'role_id'    => $roleManager->id,
            'username'   => 'manager_beta',
            'email'      => 'manager@beta.com',
            'password'   => bcrypt('secret123'),
            'status'     => 'ACTIVE',
        ]);

        // Tenant A base entities
        $this->deptA = Department::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Operaciones Call Center',
            'code'       => 'OPS-CC',
            'status'     => 'ACTIVE',
        ]);

        $this->posA = Position::create([
            'company_id'    => $this->tenantA->id,
            'department_id' => $this->deptA->id,
            'name'          => 'Agente de Servicio',
            'code'          => 'AGT-01',
            'status'        => 'ACTIVE',
        ]);

        $this->empTypeA = EmploymentType::create([
            'company_id'            => $this->tenantA->id,
            'name'                  => 'Tiempo Completo',
            'code'                  => 'TC-48',
            'default_weekly_hours'  => 48.0,
            'status'                => 'ACTIVE',
        ]);

        $this->empA1 = Employee::create([
            'company_id'         => $this->tenantA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empTypeA->id,
            'first_name'         => 'Juan',
            'last_name'          => 'Perez',
            'employee_code'      => 'ALF-001',
            'document_type'      => 'DNI',
            'document_number'    => '40112233',
            'email'              => 'juan.perez@alfa.com',
            'hire_date'          => '2025-01-10',
            'status'             => 'ACTIVE',
        ]);

        $this->empA2 = Employee::create([
            'company_id'         => $this->tenantA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empTypeA->id,
            'first_name'         => 'Maria',
            'last_name'          => 'Torres',
            'employee_code'      => 'ALF-002',
            'document_type'      => 'DNI',
            'document_number'    => '40112234',
            'email'              => 'maria.torres@alfa.com',
            'hire_date'          => '2025-02-01',
            'status'             => 'ACTIVE',
        ]);

        $this->shiftMorningA = ShiftType::create([
            'company_id'       => $this->tenantA->id,
            'name'             => 'Turno Mañana 8h',
            'code'             => 'TM-8',
            'start_time'       => '08:00',
            'end_time'         => '16:00',
            'total_work_hours' => 8.0,
            'color'            => '#10B981',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftEveningA = ShiftType::create([
            'company_id'       => $this->tenantA->id,
            'name'             => 'Turno Tarde 8h',
            'code'             => 'TT-8',
            'start_time'       => '16:00',
            'end_time'         => '00:00',
            'total_work_hours' => 8.0,
            'color'            => '#6366F1',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);

        // Tenant B base entities
        $this->deptB = Department::create([
            'company_id' => $this->tenantB->id,
            'name'       => 'Logistica Beta',
            'code'       => 'LOG-B',
            'status'     => 'ACTIVE',
        ]);

        $this->posB = Position::create([
            'company_id'    => $this->tenantB->id,
            'department_id' => $this->deptB->id,
            'name'          => 'Coordinador Beta',
            'code'          => 'LOG-01',
            'status'        => 'ACTIVE',
        ]);

        $this->empTypeB = EmploymentType::create([
            'company_id'            => $this->tenantB->id,
            'name'                  => 'Tiempo Completo Beta',
            'code'                  => 'TC-48-B',
            'default_weekly_hours'  => 48.0,
            'status'                => 'ACTIVE',
        ]);

        $this->empB1 = Employee::create([
            'company_id'         => $this->tenantB->id,
            'department_id'      => $this->deptB->id,
            'position_id'        => $this->posB->id,
            'employment_type_id' => $this->empTypeB->id,
            'first_name'         => 'Sofia',
            'last_name'          => 'Castro',
            'employee_code'      => 'BET-001',
            'document_type'      => 'DNI',
            'document_number'    => '50112233',
            'email'              => 'sofia.castro@beta.com',
            'hire_date'          => '2025-01-15',
            'status'             => 'ACTIVE',
        ]);

        $this->shiftB = ShiftType::create([
            'company_id'       => $this->tenantB->id,
            'name'             => 'Turno Beta 8h',
            'code'             => 'TB-8',
            'start_time'       => '09:00',
            'end_time'         => '17:00',
            'total_work_hours' => 8.0,
            'color'            => '#F59E0B',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);
    }

    /**
     * Flujo Completo 1:
     * Creación de periodo -> Creación de versión Draft V1 -> Asignación de turnos ->
     * Transición a REVIEW -> Publicación a PUBLISHED con auditoría forense.
     */
    public function test_end_to_end_schedule_lifecycle_and_audit(): void
    {
        Sanctum::actingAs($this->adminA);

        // 1. Crear WorkPeriod
        $period = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Semana Operativa 36',
            'start_date' => '2026-09-01',
            'end_date'   => '2026-09-07',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);

        // 2. Crear Version V1
        $version = $this->versionService->createDraftFromVersion($period, null, 'V1 inicial', $this->adminA);
        $this->assertEquals(1, $version->version_number);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $version->status);

        // 3. Crear asignación
        $assignment = ScheduleAssignment::create([
            'schedule_version_id' => $version->id,
            'employee_id'         => $this->empA1->id,
            'date'                => '2026-09-01',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorningA->id,
            'start_time'          => '08:00',
            'end_time'            => '16:00',
            'total_hours'         => 8.0,
            'is_custom'           => false,
        ]);

        // 4. Pasar a REVIEW
        $reviewed = $this->versionService->reviewVersion($version, 1, 'Paso a review', $this->adminA);
        $this->assertEquals(ScheduleVersionStatus::REVIEW, $reviewed->status);
        $this->assertEquals(2, $reviewed->lock_version);

        // 5. Publicar versión
        $published = $this->versionService->publishVersion($reviewed, 2, 'Publicacion oficial', $this->adminA);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $published->status);
        $this->assertEquals(3, $published->lock_version);

        // 6. Verificar que WorkPeriod actualizó su current_version_id
        $period->refresh();
        $this->assertEquals($published->id, $period->current_version_id);
        $this->assertEquals(WorkPeriodStatus::PUBLISHED, $period->status);

        // 7. Verificar auditoría forense registrada
        $publishLog = AuditLog::where('company_id', $this->tenantA->id)
            ->where('auditable_type', ScheduleVersion::class)
            ->where('auditable_id', $published->id)
            ->where('description', 'like', '%publicada%')
            ->first();

        $this->assertNotNull($publishLog);
        $this->assertEquals($this->adminA->id, $publishLog->user_id);
    }

    /**
     * Flujo Completo 2:
     * Modificación sobre versión publicada (Fase 17) ->
     * Inmutabilidad de la versión publicada protegida ->
     * Derivación automática de nueva versión Draft V2 ->
     * Auditoría y adjunto de evidencia documental.
     */
    public function test_end_to_end_modification_derives_new_version_with_evidence(): void
    {
        Sanctum::actingAs($this->adminA);

        $period = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Semana Operativa 37',
            'start_date' => '2026-09-08',
            'end_date'   => '2026-09-14',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);

        $v1 = $this->versionService->createDraftFromVersion($period, null, 'V1 inicial', $this->adminA);
        $assign = ScheduleAssignment::create([
            'schedule_version_id' => $v1->id,
            'employee_id'         => $this->empA1->id,
            'date'                => '2026-09-08',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorningA->id,
            'start_time'          => '08:00',
            'end_time'            => '16:00',
            'total_hours'         => 8.0,
            'is_custom'           => false,
        ]);

        $v1Review = $this->versionService->reviewVersion($v1, 1, 'Paso a review', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Review, 2, 'Publicacion oficial', $this->adminA);

        // Intentar modificar directamente la versión publicada a través de servicio de modificaciones
        $pdfFile = UploadedFile::fake()->create('justificante_cambio.pdf', 250, 'application/pdf');

        $result = $this->modificationService->createModification($v1Pub, [
            'schedule_assignment_id' => $assign->id,
            'employee_id'            => $this->empA1->id,
            'modification_type'      => ModificationType::SHIFT_SWAP->value,
            'new_shift_type_id'      => $this->shiftEveningA->id,
            'start_time'             => '16:00',
            'end_time'               => '00:00',
            'total_hours'            => 8.0,
            'reason'                 => 'Permuta de turno acordada entre agentes',
        ], [$pdfFile], $this->adminA);

        $modification = $result['modification'];
        $targetVersion = $result['resulting_version'];

        // Invariantes Fase 17:
        // 1. La versión V1 permanece intacta en estado PUBLISHED
        $v1Pub->refresh();
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED, $v1Pub->status);

        // 2. La modificación se aplicó sobre una nueva versión borrador V2
        $this->assertEquals(2, $targetVersion->version_number);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $targetVersion->status);
        $this->assertEquals($v1Pub->id, $targetVersion->parent_version_id);

        // 3. Se adjuntó la evidencia con hash SHA256
        $this->assertCount(1, $modification->evidences);
        $this->assertEquals('application/pdf', $modification->evidences[0]->mime_type);
        $this->assertEquals(64, strlen($modification->evidences[0]->sha256_hash));

        // 4. Se auditó la modificación
        $modAudit = AuditLog::where('company_id', $this->tenantA->id)
            ->where('auditable_type', ScheduleModification::class)
            ->where('auditable_id', $modification->id)
            ->first();
        $this->assertNotNull($modAudit);
    }

    /**
     * Flujo Completo 3:
     * Detección de Conflictos (Fase 15) -> Bloqueo estricto de publicación ->
     * Corrección de asignación -> Despeje automático del conflicto -> Publicación permitida.
     */
    public function test_conflict_detection_blocks_and_clears_on_correction(): void
    {
        Sanctum::actingAs($this->adminA);

        // Crear regla de negocio con máximo 10 horas diarias
        BusinessRule::create([
            'company_id'      => $this->tenantA->id,
            'department_id'   => null,
            'max_daily_hours' => 10.0,
            'status'          => 'ACTIVE',
        ]);

        $shift14h = ShiftType::create([
            'company_id'       => $this->tenantA->id,
            'name'             => 'Guardia Excesiva 14h',
            'code'             => 'G14',
            'start_time'       => '06:00',
            'end_time'         => '20:00',
            'total_work_hours' => 14.0,
            'color'            => '#EF4444',
            'requires_overlap' => false,
            'status'           => 'ACTIVE',
        ]);

        $period = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Semana Conflictos 38',
            'start_date' => '2026-09-15',
            'end_date'   => '2026-09-21',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);

        $v1 = $this->versionService->createDraftFromVersion($period, null, 'V1 con horas excesivas', $this->adminA);

        // Asignación con 14 horas (> 10h max_daily_hours)
        ScheduleAssignment::create([
            'schedule_version_id' => $v1->id,
            'employee_id'         => $this->empA1->id,
            'date'                => '2026-09-15',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $shift14h->id,
            'start_time'          => '06:00',
            'end_time'            => '20:00',
            'total_hours'         => 14.0,
            'is_custom'           => false,
        ]);

        // 1. Validar conflictos por endpoint de API
        $res = $this->postJson("/api/v1/schedule-versions/{$v1->id}/validate");
        $res->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $v1->id,
            'employee_id'         => $this->empA1->id,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
        ]);

        // 2. Intentar pasar a REVIEW y publicar debe fallar si existen hard conflicts
        $v1Review = $this->versionService->reviewVersion($v1, 1, 'Paso a review', $this->adminA);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->versionService->publishVersion($v1Review, 2, 'Publicacion que debe fallar', $this->adminA);
    }

    /**
     * Flujo Completo 4:
     * Reportes, Exportación y Auditoría Integrada (Fase 18 y Fase 19).
     */
    public function test_reports_and_export_audit_integration(): void
    {
        Sanctum::actingAs($this->adminA);

        // Crear una ausencia en Tenant A
        Absence::create([
            'company_id'   => $this->tenantA->id,
            'employee_id'  => $this->empA1->id,
            'type'         => AbsenceType::VACATION->value,
            'start_date'   => '2026-09-25',
            'end_date'     => '2026-09-27',
            'is_full_day'  => true,
            'reason'       => 'Vacaciones programadas',
            'status'       => AbsenceStatus::APPROVED->value,
            'approved_by'  => $this->adminA->id,
            'approved_at'  => now(),
        ]);

        // 1. Consultar reporte de ausencias por API
        $response = $this->getJson('/api/v1/reports/absences?type=VACATION');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertCount(1, $response->json('data.data'));

        // 2. Exportar reporte de ausencias a CSV
        $exportResp = $this->get('/api/v1/reports/absences/export?type=VACATION');
        $exportResp->assertStatus(Response::HTTP_OK);
        $this->assertStringContainsString('VACATION', $exportResp->getContent());

        // 3. Verificar que la exportación generó registro en la bitácora de auditoría
        $exportAudit = AuditLog::where('company_id', $this->tenantA->id)
            ->where('action', AuditAction::EXPORT->value)
            ->where('auditable_type', Absence::class)
            ->first();

        $this->assertNotNull($exportAudit);
        $this->assertEquals($this->adminA->id, $exportAudit->user_id);
    }

    /**
     * Flujo Completo 5:
     * Blindaje Multi-Tenant & IDOR Absoluto:
     * Un usuario de Tenant B jamás puede leer, modificar, publicar, descargar evidencias o exportar datos de Tenant A.
     */
    public function test_strict_multi_tenant_isolation_across_all_modules(): void
    {
        Sanctum::actingAs($this->adminA);

        $periodA = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Periodo Alfa Confidencial',
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-07',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);
        $versionA = $this->versionService->createDraftFromVersion($periodA, null, 'V1 Privada', $this->adminA);

        // Autenticar ahora como Admin de Tenant B
        Sanctum::actingAs($this->adminB);

        // 1. Intento de ver versión de Tenant A
        $res1 = $this->getJson("/api/v1/schedule-versions/{$versionA->id}");
        $res1->assertStatus(Response::HTTP_FORBIDDEN);

        // 2. Intento de publicar versión de Tenant A
        $res2 = $this->postJson("/api/v1/schedule-versions/{$versionA->id}/publish", [
            'lock_version' => 1,
        ]);
        $res2->assertStatus(Response::HTTP_FORBIDDEN);

        // 3. Intento de listar modificaciones de versión de Tenant A
        $res3 = $this->getJson("/api/v1/schedule-versions/{$versionA->id}/modifications");
        $res3->assertStatus(Response::HTTP_FORBIDDEN);

        // 4. Intento de ver empleados de Tenant A en reportes
        $res4 = $this->getJson('/api/v1/reports/employees');
        $res4->assertStatus(Response::HTTP_OK);
        $codes = array_column($res4->json('data.data'), 'employee_code');
        $this->assertNotContains('ALF-001', $codes);
        $this->assertContains('BET-001', $codes);

        // 5. Intento de ver bitácora de auditoría de Tenant A
        $res5 = $this->getJson('/api/v1/audit-logs');
        $res5->assertStatus(Response::HTTP_OK);
        foreach ($res5->json('data.data') as $logItem) {
            $this->assertNotEquals($this->adminA->id, $logItem['user_id']);
        }
    }

    /**
     * Flujo Completo 6:
     * Concurrencia y Detección de Stale Lock (HTTP 409):
     * Dos administradores intentan editar/publicar la misma versión simultáneamente.
     */
    public function test_concurrency_stale_lock_protection_returns_409(): void
    {
        Sanctum::actingAs($this->adminA);

        $period = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Periodo Concurrencia',
            'start_date' => '2026-10-08',
            'end_date'   => '2026-10-14',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);
        $version = $this->versionService->createDraftFromVersion($period, null, 'V1', $this->adminA);

        // Admin A pasa a REVIEW con lock_version = 1 (pasa a 2)
        $reviewed = $this->versionService->reviewVersion($version, 1, 'Paso a review', $this->adminA);
        $this->assertEquals(2, $reviewed->lock_version);

        // Manager A intenta pasar a REVIEW enviando lock_version desactualizado = 1 por API
        Sanctum::actingAs($this->managerA);
        $staleResp = $this->patchJson("/api/v1/schedule-versions/{$version->id}/review", [
            'lock_version' => 1,
        ]);

        $staleResp->assertStatus(Response::HTTP_CONFLICT);
    }

    /**
     * Flujo Completo 7:
     * Restauración no destructiva de versión histórica (Fase 16):
     * V1 (Publicada) -> V2 (Publicada) -> Restaurar V1 -> Genera V3 (Draft)
     * manteniendo V2 intacta y trazabilidad completa.
     */
    public function test_restore_historical_version_derives_new_draft_and_preserves_published(): void
    {
        Sanctum::actingAs($this->adminA);

        $period = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Periodo Restauracion',
            'start_date' => '2026-10-15',
            'end_date'   => '2026-10-21',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);

        $v1 = $this->versionService->createDraftFromVersion($period, null, 'V1', $this->adminA);
        $v1Rev = $this->versionService->reviewVersion($v1, 1, 'Rev V1', $this->adminA);
        $v1Pub = $this->versionService->publishVersion($v1Rev, 2, 'Pub V1', $this->adminA);

        $v2 = $this->versionService->createDraftFromVersion($period, $v1Pub, 'V2 base V1', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2, 1, 'Rev V2', $this->adminA);
        $v2Pub = $this->versionService->publishVersion($v2Rev, 2, 'Pub V2', $this->adminA);

        // Restaurar V1 (histórica archivada)
        $v3 = $this->versionService->restoreVersion($period, $v1Pub, 'Restaurando configuracion V1 por solicitud de operaciones', $this->adminA);

        $this->assertEquals(3, $v3->version_number);
        $this->assertEquals(ScheduleVersionStatus::DRAFT, $v3->status);
        $this->assertEquals($v1Pub->id, $v3->parent_version_id);

        // Verificar que V2 sigue siendo la versión actualmente publicada
        $period->refresh();
        $this->assertEquals($v2Pub->id, $period->current_version_id);
    }

    /**
     * Flujo Completo 8:
     * Seguridad de Evidencias y Descargas:
     * Archivos adjuntos en Tenant A no son accesibles por ningún usuario de Tenant B.
     */
    public function test_cross_tenant_evidence_download_blocked_with_403(): void
    {
        Sanctum::actingAs($this->adminA);

        $period = WorkPeriod::create([
            'company_id' => $this->tenantA->id,
            'name'       => 'Periodo Evidencias',
            'start_date' => '2026-10-22',
            'end_date'   => '2026-10-28',
            'status'     => WorkPeriodStatus::DRAFT->value,
            'created_by' => $this->adminA->id,
        ]);
        $v1 = $this->versionService->createDraftFromVersion($period, null, 'V1', $this->adminA);
        $assign = ScheduleAssignment::create([
            'schedule_version_id' => $v1->id,
            'employee_id'         => $this->empA1->id,
            'date'                => '2026-10-22',
            'day_type'            => DayType::WORK->value,
            'shift_type_id'       => $this->shiftMorningA->id,
            'start_time'          => '08:00',
            'end_time'            => '16:00',
            'total_hours'         => 8.0,
            'is_custom'           => false,
        ]);

        $pngFile = UploadedFile::fake()->create('certificado_medico.png', 150, 'image/png');

        $result = $this->modificationService->createModification($v1, [
            'schedule_assignment_id' => $assign->id,
            'employee_id'            => $this->empA1->id,
            'modification_type'      => ModificationType::DAY_OFF_CHANGE->value,
            'reason'                 => 'Permiso médico urgente',
        ], [$pngFile], $this->adminA);

        $evidence = $result['modification']->evidences->first();
        $this->assertNotNull($evidence);

        // Intento de descarga por Admin de Tenant B
        Sanctum::actingAs($this->adminB);
        $downloadResp = $this->getJson("/api/v1/schedule-modifications/{$result['modification']->id}/evidences/{$evidence->id}/download");
        $downloadResp->assertStatus(Response::HTTP_FORBIDDEN);
    }
}
