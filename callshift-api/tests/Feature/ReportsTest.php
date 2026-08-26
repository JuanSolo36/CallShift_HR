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
use App\Services\Reports\ReportService;
use App\Services\Schedule\ScheduleVersionService;
use App\Services\Schedule\ScheduleModificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\Sanctum;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $adminA;
    protected User $managerA;
    protected User $viewerA;
    protected User $employeeUserA;
    protected User $adminB;
    protected Department $deptA;
    protected Position $posA;
    protected EmploymentType $empTypeA;
    protected Employee $empA;
    protected Employee $empB;
    protected ShiftType $shiftA;
    protected WorkPeriod $periodA;
    protected ScheduleVersion $versionA1;
    protected ScheduleAssignment $assignmentA1;
    protected Absence $absenceA;

    protected ReportService $reportService;
    protected ScheduleVersionService $versionService;
    protected ScheduleModificationService $modificationService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->reportService = app(ReportService::class);
        $this->versionService = app(ScheduleVersionService::class);
        $this->modificationService = app(ScheduleModificationService::class);

        $this->setupBaseData();
    }

    private function setupBaseData(): void
    {
        $roleAdmin = Role::firstOrCreate(['code' => RoleCode::HR_ADMIN->value], ['name' => 'HR Admin', 'hierarchy_level' => 2]);
        $roleManager = Role::firstOrCreate(['code' => RoleCode::MANAGER->value], ['name' => 'Manager', 'hierarchy_level' => 3]);
        $roleViewer = Role::firstOrCreate(['code' => RoleCode::VIEWER->value], ['name' => 'Viewer', 'hierarchy_level' => 6]);
        $roleEmp = Role::firstOrCreate(['code' => RoleCode::EMPLOYEE->value], ['name' => 'Employee', 'hierarchy_level' => 5]);

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

        $this->managerA = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $roleManager->id,
            'username'   => 'manager_a',
            'email'      => 'manager_a@alfa.com',
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

        $this->employeeUserA = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $roleEmp->id,
            'username'   => 'emp_user_a',
            'email'      => 'emp_user_a@alfa.com',
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

        $this->absenceA = Absence::create([
            'company_id'   => $this->companyA->id,
            'employee_id'  => $this->empA->id,
            'type'         => AbsenceType::SICK_LEAVE->value,
            'start_date'   => '2026-08-28',
            'end_date'     => '2026-08-28',
            'is_full_day'  => true,
            'reason'       => 'Cita médica certificada',
            'status'       => AbsenceStatus::APPROVED->value,
            'approved_by'  => $this->adminA->id,
            'approved_at'  => now(),
        ]);
    }

    /** 1. Reporte de empleados retorna los datos correctos */
    public function test_employee_report_returns_correct_data(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/employees');
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        $this->assertEquals('EMP-A001', $data[0]['employee_code']);
        $this->assertEquals('Carlos Mendoza', $data[0]['full_name']);
    }

    /** 2. Reporte de empleados tiene scope de tenant */
    public function test_employee_report_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/employees');
        $data = $response->json('data.data');

        // No debe aparecer el empleado de Company B
        $codes = array_column($data, 'employee_code');
        $this->assertContains('EMP-A001', $codes);
        $this->assertNotContains('EMP-B001', $codes);
    }

    /** 3. Reporte de horarios retorna las asignaciones correctas */
    public function test_schedule_report_returns_correct_assignments(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson("/api/v1/reports/schedules?work_period_id={$this->periodA->id}");
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        $this->assertEquals('2026-08-24', $data[0]['date']);
        $this->assertEquals(8.0, (float)$data[0]['total_hours']);
    }

    /** 4. Reporte de horarios respeta el filtro de versión */
    public function test_schedule_report_respects_version(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson("/api/v1/reports/schedules?schedule_version_id={$this->versionA1->id}");
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        $this->assertEquals($this->versionA1->id, $data[0]['schedule_version_id']);
    }

    /** 5. Reporte de horas calcula los totales por empleado */
    public function test_hours_report_calculates_totals(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson("/api/v1/reports/hours?work_period_id={$this->periodA->id}");
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data');
        $this->assertEquals(8.0, $data['summary']['total_hours']);
        $this->assertEquals(1, $data['summary']['total_employees']);
        $this->assertEquals(8.0, $data['employees'][0]['total_work_hours']);
    }

    /** 6. Reporte de ausencias retorna los registros correspondientes */
    public function test_absence_report_returns_correct_records(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/absences');
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        $this->assertEquals('2026-08-28', $data[0]['start_date']);
        $this->assertEquals('SICK_LEAVE', $data[0]['type']);
    }

    /** 7. Reporte de modificaciones retorna snapshots y datos forenses */
    public function test_modification_report_returns_snapshots(): void
    {
        $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::TIME_CHANGE->value,
            'start_time'             => '09:00',
            'end_time'               => '17:00',
            'total_hours'            => 8.0,
            'reason'                 => 'Ajuste de reporte test',
        ], [], $this->adminA);

        Sanctum::actingAs($this->adminA);

        $response = $this->getJson("/api/v1/reports/modifications?schedule_version_id={$this->versionA1->id}");
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
        $this->assertNotNull($data[0]['previous_data']);
        $this->assertNotNull($data[0]['new_data']);
    }

    /** 8. Reporte de auditoría utiliza AuditService de Fase 18 */
    public function test_audit_report_uses_audit_service(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/audit');
        $response->assertStatus(Response::HTTP_OK);

        $data = $response->json('data.data');
        $this->assertNotEmpty($data);
    }

    /** 9. Rechazo de acceso cross-tenant a reportes */
    public function test_cross_tenant_report_access_is_rejected(): void
    {
        Sanctum::actingAs($this->adminB);

        // Pedir periodo de Empresa A desde usuario de Empresa B no debe devolver datos de A
        $response = $this->getJson("/api/v1/reports/schedules?work_period_id={$this->periodA->id}");
        $response->assertStatus(Response::HTTP_OK);
        $this->assertEmpty($response->json('data.data'));
    }

    /** 10. Manager no puede acceder a datos de otro tenant */
    public function test_manager_cannot_access_other_tenant_data(): void
    {
        Sanctum::actingAs($this->managerA);

        $response = $this->getJson('/api/v1/reports/employees');
        $data = $response->json('data.data');

        foreach ($data as $emp) {
            $this->assertNotEquals('EMP-B001', $emp['employee_code']);
        }
    }

    /** 11. Permisos RBAC son aplicados estrictamente */
    public function test_report_permissions_are_enforced(): void
    {
        Sanctum::actingAs($this->employeeUserA);

        // Empleado sin permiso de reportes debe ser bloqueado
        $response = $this->getJson('/api/v1/reports/employees');
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** 12. Exportación requiere permisos RBAC específicos (VIEWER denegado) */
    public function test_export_requires_permission(): void
    {
        Sanctum::actingAs($this->viewerA);

        $response = $this->getJson('/api/v1/reports/employees/export');
        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /** 13. Exportación de empleados es tenant scoped */
    public function test_employee_export_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/reports/employees/export');
        $response->assertStatus(Response::HTTP_OK);

        $csv = $response->getContent();
        $this->assertStringContainsString('EMP-A001', $csv);
        $this->assertStringNotContainsString('EMP-B001', $csv);
    }

    /** 14. Exportación de horarios es tenant scoped */
    public function test_schedule_export_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/reports/schedules/export');
        $response->assertStatus(Response::HTTP_OK);

        $csv = $response->getContent();
        $this->assertStringContainsString('EMP-A001', $csv);
        $this->assertStringNotContainsString('EMP-B001', $csv);
    }

    /** 15. Exportación de horas es tenant scoped */
    public function test_hours_export_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/reports/hours/export');
        $response->assertStatus(Response::HTTP_OK);

        $csv = $response->getContent();
        $this->assertStringContainsString('EMP-A001', $csv);
        $this->assertStringNotContainsString('EMP-B001', $csv);
    }

    /** 16. Exportación de ausencias es tenant scoped */
    public function test_absence_export_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/reports/absences/export');
        $response->assertStatus(Response::HTTP_OK);

        $csv = $response->getContent();
        $this->assertStringContainsString('SICK_LEAVE', $csv);
    }

    /** 17. Exportación de modificaciones es tenant scoped */
    public function test_modification_export_is_tenant_scoped(): void
    {
        $this->modificationService->createModification($this->versionA1, [
            'schedule_assignment_id' => $this->assignmentA1->id,
            'employee_id'            => $this->empA->id,
            'modification_type'      => ModificationType::DAY_OFF_CHANGE->value,
            'reason'                 => 'Export mod test',
        ], [], $this->adminA);

        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/reports/modifications/export');
        $response->assertStatus(Response::HTTP_OK);

        $csv = $response->getContent();
        $this->assertStringContainsString('DAY_OFF_CHANGE', $csv);
    }

    /** 18. Exportación de auditoría es tenant scoped */
    public function test_audit_export_is_tenant_scoped(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->get('/api/v1/reports/audit/export');
        $response->assertStatus(Response::HTTP_OK);

        $csv = $response->getContent();
        $this->assertStringContainsString('ID,Fecha,Usuario,Accion,Entidad', $csv);
    }

    /** 19. Exportación genera evento de auditoría en AuditLog */
    public function test_export_generates_audit_event(): void
    {
        Sanctum::actingAs($this->adminA);

        $this->get('/api/v1/reports/employees/export');

        $log = AuditLog::where('action', AuditAction::EXPORT->value)
            ->where('company_id', $this->companyA->id)
            ->where('auditable_type', Employee::class)
            ->first();

        $this->assertNotNull($log);
        $this->assertEquals($this->adminA->id, $log->user_id);
    }

    /** 20. Consultas grandes están debidamente paginadas */
    public function test_large_report_query_is_paginated(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/employees?per_page=10');
        $response->assertStatus(Response::HTTP_OK);

        $this->assertArrayHasKey('meta', $response->json('data'));
        $this->assertEquals(10, $response->json('data.meta.per_page'));
    }

    /** 21. Filtros de búsqueda son aplicados correctamente */
    public function test_report_filters_are_applied_correctly(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/employees?search=Carlos');
        $response->assertStatus(Response::HTTP_OK);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);

        $responseEmpty = $this->getJson('/api/v1/reports/employees?search=InexistenteXYZ');
        $responseEmpty->assertStatus(Response::HTTP_OK);
        $this->assertEmpty($responseEmpty->json('data.data'));
    }

    /** 22. Filtros con IDs inválidos o de otro tenant devuelven colección vacía */
    public function test_invalid_report_filters_are_rejected(): void
    {
        Sanctum::actingAs($this->adminA);

        $response = $this->getJson('/api/v1/reports/schedules?work_period_id=999999');
        $response->assertStatus(Response::HTTP_OK);
        $this->assertEmpty($response->json('data.data'));
    }
}
