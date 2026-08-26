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
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use App\Services\Schedule\ScheduleVersionService;
use App\Services\Schedule\ScheduleModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;
use BadMethodCallException;
use Laravel\Sanctum\Sanctum;

class AuditTest extends TestCase
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
    protected ShiftType $shiftA;
    protected WorkPeriod $periodA;
    protected ScheduleVersion $versionA1;
    protected ScheduleAssignment $assignmentA1;

    protected ScheduleVersionService $versionService;
    protected ScheduleModificationService $modificationService;
    protected AuditService $auditService;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');

        $this->versionService = app(ScheduleVersionService::class);
        $this->modificationService = app(ScheduleModificationService::class);
        $this->auditService = app(AuditService::class);

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

        $this->shiftA = ShiftType::create([
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
            'shift_type_id'       => $this->shiftA->id,
            'start_time'          => '08:00',
            'end_time'            => '16:00',
            'total_hours'         => 8.0,
            'is_custom'           => false,
        ]);
    }

    /** 1. Operación CREATE genera AuditLog */
    public function test_create_operation_generates_audit_log(): void
    {
        $log = AuditLog::where('auditable_type', Department::class)
            ->where('auditable_id', $this->deptA->id)
            ->where('action', AuditAction::CREATE->value)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->companyA->id, $log->company_id);
    }

    /** 2. Operación UPDATE genera old_values y new_values con snapshot simétrico */
    public function test_update_operation_generates_old_and_new_values(): void
    {
        $this->deptA->name = 'Operaciones Alfa Modificado';
        $this->deptA->save();

        $log = AuditLog::where('auditable_type', Department::class)
            ->where('auditable_id', $this->deptA->id)
            ->where('action', AuditAction::UPDATE->value)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('Operaciones Alfa', $log->old_values['name']);
        $this->assertEquals('Operaciones Alfa Modificado', $log->new_values['name']);
    }

    /** 3. Operación DELETE genera AuditLog con valores originales */
    public function test_delete_operation_generates_audit_log(): void
    {
        $dept = Department::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Depto Temporal',
            'code'       => 'TEMP-01',
            'status'     => 'ACTIVE',
        ]);

        $dept->delete();

        $log = AuditLog::where('auditable_type', Department::class)
            ->where('auditable_id', $dept->id)
            ->where('action', AuditAction::DELETE->value)
            ->first();

        $this->assertNotNull($log);
        $this->assertArrayHasKey('name', $log->old_values);
        $this->assertEquals('Depto Temporal', $log->old_values['name']);
    }

    /** 4. Publicación de versión genera AuditLog con trazabilidad */
    public function test_publish_version_generates_audit_log(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo para publicar', $this->adminA);
        $vPub = $this->versionService->publishVersion($vRev, 2, 'Publicación oficial', $this->adminA);

        $log = AuditLog::where('auditable_type', ScheduleVersion::class)
            ->where('auditable_id', $vPub->id)
            ->where('description', 'like', '%publicada oficialmente%')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->companyA->id, $log->company_id);
    }

    /** 5. Restauración de versión genera AuditLog con trazabilidad */
    public function test_restore_version_generates_audit_log(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo', $this->adminA);
        $vPub = $this->versionService->publishVersion($vRev, 2, 'Pub V1', $this->adminA);

        $v2 = $this->versionService->createDraftFromVersion($this->periodA, $vPub, 'V2 Borrador', $this->adminA);
        $v2Rev = $this->versionService->reviewVersion($v2, 1, 'V2 Rev', $this->adminA);
        $this->versionService->publishVersion($v2Rev, 2, 'Pub V2', $this->adminA);

        $vRestored = $this->versionService->restoreVersion($this->periodA, $vPub, 'Restaurar V1', $this->adminA);

        $log = AuditLog::where('auditable_type', ScheduleVersion::class)
            ->where('auditable_id', $vRestored->id)
            ->where('description', 'like', '%Restauración%')
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->companyA->id, $log->company_id);
    }

    /** 6. Modificación de horario genera AuditLog */
    public function test_schedule_modification_generates_audit_log(): void
    {
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::TIME_CHANGE->value,
            'start_time'             => '09:00',
            'end_time'               => '17:00',
            'total_hours'            => 8.0,
            'reason'                 => 'Ajuste de horario auditado',
        ], [], $this->adminA);

        $log = AuditLog::where('auditable_type', ScheduleModification::class)
            ->where('auditable_id', $result['modification']->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('TIME_CHANGE', $log->description);
    }

    /** 7. Adjuntar evidencia documental genera AuditLog */
    public function test_evidence_attach_generates_audit_log(): void
    {
        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::LEAVE_PERMISSION->value,
            'reason'                 => 'Permiso médico para adjuntar evidencia',
        ], [], $this->adminA);

        $file = UploadedFile::fake()->create('doc_attach.pdf', 100, 'application/pdf');
        $evidence = $this->modificationService->attachEvidence($result['modification'], $file, $this->adminA);

        $log = AuditLog::where('auditable_type', ModificationEvidence::class)
            ->where('auditable_id', $evidence->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('doc_attach.pdf', $log->description);
    }

    /** 8. Eliminación de evidencia documental genera AuditLog */
    public function test_evidence_delete_generates_audit_log(): void
    {
        $file = UploadedFile::fake()->create('ev_borrar.pdf', 100, 'application/pdf');

        $result = $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::OTHER->value,
            'reason'                 => 'Evidencia a borrar',
        ], [], $this->adminA);

        $evidence = $this->modificationService->attachEvidence($result['modification'], $file, $this->adminA);
        $evidenceId = $evidence->id;

        $this->modificationService->deleteEvidence($evidence, $this->adminA);

        $log = AuditLog::where('auditable_type', ModificationEvidence::class)
            ->where('auditable_id', $evidenceId)
            ->where('action', AuditAction::DELETE->value)
            ->first();

        $this->assertNotNull($log);
    }

    /** 9. Inicio de sesión genera AuditLog con acción LOGIN */
    public function test_login_generates_audit_log(): void
    {
        AuditService::logLogin($this->adminA, '127.0.0.1', 'PHPUnit Browser');

        $log = AuditLog::where('user_id', $this->adminA->id)
            ->where('action', AuditAction::LOGIN->value)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals('127.0.0.1', $log->ip_address);
    }

    /** 10. Cierre de sesión genera AuditLog con acción LOGOUT */
    public function test_logout_generates_audit_log(): void
    {
        AuditService::logLogout($this->adminA, '127.0.0.1', 'PHPUnit Browser');

        $log = AuditLog::where('user_id', $this->adminA->id)
            ->where('action', AuditAction::LOGOUT->value)
            ->first();

        $this->assertNotNull($log);
    }

    /** 11. Inmutabilidad: AuditLog no puede modificarse */
    public function test_audit_log_cannot_be_modified(): void
    {
        $log = AuditLog::first();
        $this->assertNotNull($log);

        $this->expectException(BadMethodCallException::class);
        $log->description = 'Intento de alteración fraudulenta';
        $log->save();
    }

    /** 12. Inmutabilidad: AuditLog no puede eliminarse */
    public function test_audit_log_cannot_be_deleted(): void
    {
        $log = AuditLog::first();
        $this->assertNotNull($log);

        $this->expectException(BadMethodCallException::class);
        $log->delete();
    }

    /** 13. Aislamiento Multi-Tenant: Rechazo de acceso a logs de otra empresa */
    public function test_cross_tenant_audit_access_is_rejected(): void
    {
        $logA = AuditLog::where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($logA);

        Sanctum::actingAs($this->adminB);

        $response = $this->getJson("/api/v1/audit-logs/{$logA->id}");
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** 14. Filtros de auditoría respetan estrictamente el aislamiento de tenant */
    public function test_audit_filters_are_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/audit-logs');
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);

        foreach ($data as $item) {
            $this->assertEquals($this->companyA->id, $item['company_id']);
        }
    }

    /** 15. Comparación correcta de old_values y new_values */
    public function test_old_values_and_new_values_are_correct(): void
    {
        $oldName = $this->posA->name;
        $this->posA->name = 'Operador Senior Especializado';
        $this->posA->save();

        $log = AuditLog::where('auditable_type', Position::class)
            ->where('auditable_id', $this->posA->id)
            ->where('action', AuditAction::UPDATE->value)
            ->latest('id')
            ->first();

        $this->assertEquals($oldName, $log->old_values['name']);
        $this->assertEquals('Operador Senior Especializado', $log->new_values['name']);
    }

    /** 16. Campos sensibles (passwords, tokens, secrets) nunca se almacenan en texto plano */
    public function test_sensitive_fields_are_not_logged(): void
    {
        $values = [
            'username' => 'testuser',
            'password' => 'secret_password_123',
            'token'    => 'sanctum_plain_text_token',
            'email'    => 'test@test.com',
        ];

        $sanitized = AuditService::sanitizeValues($values);

        $this->assertEquals('testuser', $sanitized['username']);
        $this->assertEquals('test@test.com', $sanitized['email']);
        $this->assertEquals('[REDACTED]', $sanitized['password']);
        $this->assertEquals('[REDACTED]', $sanitized['token']);
    }

    /** 17. Rollback de transacción no deja logs de auditoría huérfanos */
    public function test_failed_transaction_does_not_leave_orphan_success_audit(): void
    {
        $logCountBefore = AuditLog::count();

        try {
            DB::transaction(function () {
                $dept = Department::create([
                    'company_id' => $this->companyA->id,
                    'name'       => 'Depto Fallido',
                    'code'       => 'FAIL-01',
                    'status'     => 'ACTIVE',
                ]);

                throw new \Exception('Falla intencional en transacción');
            });
        } catch (\Throwable $e) {
            // Rollback esperado
        }

        $this->assertEquals($logCountBefore, AuditLog::count());
    }

    /** 18. Publicación fallida revierte auditoría */
    public function test_publish_transaction_rolls_back_audit_when_publication_fails(): void
    {
        $vRev = $this->versionService->reviewVersion($this->versionA1, 1, 'Listo', $this->adminA);

        $logCountBefore = AuditLog::count();

        try {
            // Forzar lock_version incorrecto para simular aborto
            $this->versionService->publishVersion($vRev, 999, 'Pub Fallido', $this->adminA);
            $this->fail('Debió fallar');
        } catch (\Throwable $e) {
            // Rollback
        }

        $this->assertEquals(0, AuditLog::where('description', 'like', '%publicada oficialmente%')->count());
    }

    /** 19. Restauración fallida revierte auditoría */
    public function test_restore_transaction_rolls_back_audit_when_restore_fails(): void
    {
        $logCountBefore = AuditLog::count();

        try {
            // Restaurar versión en borrador debe fallar
            $this->versionService->restoreVersion($this->periodA, $this->versionA1, 'Restaurar inválido', $this->adminA);
            $this->fail('Debió fallar');
        } catch (\Throwable $e) {
            // Rollback
        }

        $this->assertEquals(0, AuditLog::where('description', 'like', '%Restauración de versión%')->count());
    }

    /** 20. Modificación fallida revierte auditoría */
    public function test_modification_transaction_rolls_back_audit_when_modification_fails(): void
    {
        $logCountBefore = AuditLog::count();

        try {
            $this->modificationService->createModification($this->versionA1, [
                'schedule_assignment_id' => $this->assignmentA1->id,
                'employee_id'            => $this->empA->id,
                'modification_type'      => ModificationType::SHIFT_CHANGE->value,
                'shift_type_id'          => 999999, // Inexistente
                'reason'                 => 'Fallo forzado',
            ], [], $this->adminA);
            $this->fail('Debió fallar');
        } catch (\Throwable $e) {
            // Rollback
        }

        $this->assertEquals(0, AuditLog::where('auditable_type', ScheduleModification::class)->count());
    }

    /** 21. Exportación de auditoría requiere permisos RBAC */
    public function test_audit_export_requires_permission(): void
    {
        Sanctum::actingAs($this->viewerA);

        $response = $this->getJson('/api/v1/audit-logs/export');
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** 22. Exportación de auditoría tiene scope de tenant */
    public function test_audit_export_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/audit-logs/export');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertStringContainsString('text/csv', (string)$response->headers->get('content-type'));

        $csvContent = $response->getContent();
        $this->assertStringContainsString('ID,Fecha,Usuario,Accion,Entidad', $csvContent);
    }

    /** 23. Exportación de auditoría genera evento de auditoría EXPORT */
    public function test_audit_export_generates_export_audit_event(): void
    {
        $this->auditService->exportLogsCsv([], $this->adminA);

        $log = AuditLog::where('action', AuditAction::EXPORT->value)
            ->where('company_id', $this->companyA->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->adminA->id, $log->user_id);
    }

    /** 24. Detalle de auditoría no expone credenciales ni datos sensibles */
    public function test_audit_details_do_not_expose_sensitive_credentials(): void
    {
        $user = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $this->adminA->role_id,
            'username'   => 'new_user',
            'email'      => 'new_user@alfa.com',
            'password'   => bcrypt('super_secret_password'),
            'status'     => 'ACTIVE',
        ]);

        $log = AuditLog::where('auditable_type', User::class)
            ->where('auditable_id', $user->id)
            ->first();

        $this->assertNotNull($log);
        if ($log->new_values && isset($log->new_values['password'])) {
            $this->assertEquals('[REDACTED]', $log->new_values['password']);
        }
    }
}
