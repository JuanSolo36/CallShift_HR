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
use App\Enums\WorkPeriodType;
use App\Enums\WorkPeriodStatus;
use App\Enums\ScheduleVersionStatus;
use App\Enums\DayType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

class ScheduleEditorTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $hrUserA;
    protected User $viewerUserA;
    protected User $hrUserB;
    protected Department $deptA;
    protected Department $deptA2;
    protected Position $posA;
    protected EmploymentType $empTypeA;
    protected Employee $employeeA1;
    protected Employee $employeeA2;
    protected Employee $employeeADept2;
    protected Employee $employeeB;
    protected ShiftType $shiftDayA;
    protected ShiftType $shiftNightA;
    protected ShiftType $shift24hA;
    protected ShiftType $shiftInactiveA;
    protected ShiftType $shiftB;
    protected WorkPeriod $workPeriodA;
    protected ScheduleVersion $versionA;
    protected WorkPeriod $workPeriodB;
    protected ScheduleVersion $versionB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        // Crear Empresa A y Empresa B
        $this->companyA = Company::create([
            'name'        => 'Company A Logistics',
            'legal_name'  => 'Company A Logistics SAS',
            'tax_id'      => '900111222-1',
            'email'       => 'contact@companya.com',
            'country'     => 'COL',
            'timezone'    => 'America/Bogota',
            'currency'    => 'COP',
            'status'      => 'ACTIVE',
        ]);

        $this->companyB = Company::create([
            'name'        => 'Company B Security',
            'legal_name'  => 'Company B Security LTDA',
            'tax_id'      => '900333444-2',
            'email'       => 'contact@companyb.com',
            'country'     => 'COL',
            'timezone'    => 'America/Bogota',
            'currency'    => 'COP',
            'status'      => 'ACTIVE',
        ]);

        $hrRole = Role::where('code', RoleCode::HR_ADMIN->value)->first();
        $viewerRole = Role::where('code', RoleCode::VIEWER->value)->first();

        // Usuarios
        $this->hrUserA = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $hrRole->id,
            'username'   => 'hr.admin.a',
            'email'      => 'hr.admin.a@companya.com',
            'password'   => 'Password123!',
            'status'     => 'ACTIVE',
        ]);

        $this->viewerUserA = User::create([
            'company_id' => $this->companyA->id,
            'role_id'    => $viewerRole->id,
            'username'   => 'viewer.a',
            'email'      => 'viewer.a@companya.com',
            'password'   => 'Password123!',
            'status'     => 'ACTIVE',
        ]);

        $this->hrUserB = User::create([
            'company_id' => $this->companyB->id,
            'role_id'    => $hrRole->id,
            'username'   => 'hr.admin.b',
            'email'      => 'hr.admin.b@companyb.com',
            'password'   => 'Password123!',
            'status'     => 'ACTIVE',
        ]);

        // Estructura A
        $this->deptA = Department::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Operaciones Call Center',
            'code'       => 'OPS-CC',
            'status'     => 'ACTIVE',
        ]);

        $this->deptA2 = Department::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Finanzas y Contabilidad',
            'code'       => 'FIN-01',
            'status'     => 'ACTIVE',
        ]);

        $this->posA = Position::create([
            'company_id'    => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'name'          => 'Agente Bilingüe',
            'code'          => 'AGT-BIL',
            'status'        => 'ACTIVE',
        ]);

        $this->empTypeA = EmploymentType::create([
            'company_id'         => $this->companyA->id,
            'name'               => 'Tiempo Completo',
            'code'               => 'FT-48',
            'weekly_hours_base'  => 48.0,
            'overtime_allowed'   => true,
            'status'             => 'ACTIVE',
        ]);

        // Empleados A
        $this->employeeA1 = Employee::create([
            'company_id'         => $this->companyA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empTypeA->id,
            'employee_code'      => 'EMP-001',
            'first_name'         => 'Carlos',
            'last_name'          => 'Mendoza',
            'document_number'    => '10101010',
            'email'              => 'carlos@companya.com',
            'hire_date'          => '2025-01-01',
            'status'             => 'ACTIVE',
        ]);

        $this->employeeA2 = Employee::create([
            'company_id'         => $this->companyA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empTypeA->id,
            'employee_code'      => 'EMP-002',
            'first_name'         => 'Laura',
            'last_name'          => 'Gomez',
            'document_number'    => '20202020',
            'email'              => 'laura@companya.com',
            'hire_date'          => '2025-01-01',
            'status'             => 'ACTIVE',
        ]);

        $this->employeeADept2 = Employee::create([
            'company_id'         => $this->companyA->id,
            'department_id'      => $this->deptA2->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empTypeA->id,
            'employee_code'      => 'EMP-003',
            'first_name'         => 'Andres',
            'last_name'          => 'Financiero',
            'document_number'    => '30303030',
            'email'              => 'andres@companya.com',
            'hire_date'          => '2025-01-01',
            'status'             => 'ACTIVE',
        ]);

        // Empleado B
        $deptB = Department::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Vigilancia',
            'code'       => 'VIG-01',
            'status'     => 'ACTIVE',
        ]);
        $posB = Position::create([
            'company_id'    => $this->companyB->id,
            'department_id' => $deptB->id,
            'name'          => 'Guardia',
            'code'          => 'GRD-01',
            'status'        => 'ACTIVE',
        ]);
        $empTypeB = EmploymentType::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Jornada Continua',
            'code'              => 'JC-48',
            'weekly_hours_base' => 48.0,
            'status'            => 'ACTIVE',
        ]);
        $this->employeeB = Employee::create([
            'company_id'         => $this->companyB->id,
            'department_id'      => $deptB->id,
            'position_id'        => $posB->id,
            'employment_type_id' => $empTypeB->id,
            'employee_code'      => 'EMP-B01',
            'first_name'         => 'Roberto',
            'last_name'          => 'Silva',
            'document_number'    => '40404040',
            'email'              => 'roberto@companyb.com',
            'hire_date'          => '2025-01-01',
            'status'             => 'ACTIVE',
        ]);

        // Turnos A (Diurno, Nocturno, 24h, Inactivo)
        $this->shiftDayA = ShiftType::create([
            'company_id'               => $this->companyA->id,
            'name'                     => 'Turno Mañana (08-17)',
            'code'                     => 'TM-08',
            'color_hex'                => '#3B82F6',
            'start_time'               => '08:00:00',
            'end_time'                 => '17:00:00',
            'break_duration_minutes'   => 60,
            'total_work_hours'         => 8.0,
            'crosses_midnight'         => false,
            'status'                   => 'ACTIVE',
        ]);

        $this->shiftNightA = ShiftType::create([
            'company_id'               => $this->companyA->id,
            'name'                     => 'Turno Noche (22-06)',
            'code'                     => 'TN-22',
            'color_hex'                => '#8B5CF6',
            'start_time'               => '22:00:00',
            'end_time'                 => '06:00:00',
            'break_duration_minutes'   => 0,
            'total_work_hours'         => 8.0,
            'crosses_midnight'         => true,
            'status'                   => 'ACTIVE',
        ]);

        $this->shift24hA = ShiftType::create([
            'company_id'               => $this->companyA->id,
            'name'                     => 'Turno 24 Horas',
            'code'                     => 'T24-08',
            'color_hex'                => '#F59E0B',
            'start_time'               => '08:00:00',
            'end_time'                 => '08:00:00',
            'break_duration_minutes'   => 120,
            'total_work_hours'         => 22.0,
            'crosses_midnight'         => true,
            'status'                   => 'ACTIVE',
        ]);

        $this->shiftInactiveA = ShiftType::create([
            'company_id'               => $this->companyA->id,
            'name'                     => 'Turno Descontinuado',
            'code'                     => 'TD-00',
            'color_hex'                => '#9CA3AF',
            'start_time'               => '09:00:00',
            'end_time'                 => '18:00:00',
            'break_duration_minutes'   => 60,
            'total_work_hours'         => 8.0,
            'crosses_midnight'         => false,
            'status'                   => 'INACTIVE',
        ]);

        // Turno B
        $this->shiftB = ShiftType::create([
            'company_id'               => $this->companyB->id,
            'name'                     => 'Turno Vigilancia B',
            'code'                     => 'TV-B',
            'color_hex'                => '#10B981',
            'start_time'               => '07:00:00',
            'end_time'                 => '19:00:00',
            'break_duration_minutes'   => 60,
            'total_work_hours'         => 11.0,
            'crosses_midnight'         => false,
            'status'                   => 'ACTIVE',
        ]);

        // Periodo y Versión A (Semana 35: 2026-08-24 al 2026-08-30)
        $this->workPeriodA = WorkPeriod::create([
            'company_id'    => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'name'          => 'Semana 35 - Operaciones',
            'period_type'   => WorkPeriodType::WEEKLY,
            'start_date'    => '2026-08-24',
            'end_date'      => '2026-08-30',
            'status'        => WorkPeriodStatus::DRAFT,
            'created_by'    => $this->hrUserA->id,
        ]);

        $this->versionA = ScheduleVersion::create([
            'work_period_id' => $this->workPeriodA->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'score'          => 100.00,
            'created_by'     => $this->hrUserA->id,
        ]);

        $this->workPeriodA->update(['current_version_id' => $this->versionA->id]);

        // Periodo y Versión B
        $this->workPeriodB = WorkPeriod::create([
            'company_id'    => $this->companyB->id,
            'department_id' => $deptB->id,
            'name'          => 'Semana 35 - Vigilancia',
            'period_type'   => WorkPeriodType::WEEKLY,
            'start_date'    => '2026-08-24',
            'end_date'      => '2026-08-30',
            'status'        => WorkPeriodStatus::DRAFT,
            'created_by'    => $this->hrUserB->id,
        ]);

        $this->versionB = ScheduleVersion::create([
            'work_period_id' => $this->workPeriodB->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'score'          => 100.00,
            'created_by'     => $this->hrUserB->id,
        ]);
        $this->workPeriodB->update(['current_version_id' => $this->versionB->id]);
    }

    // TEST 1: Usuario autorizado consulta la malla por work period
    public function test_authorized_user_can_fetch_schedule_grid_by_work_period(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $response = $this->getJson("/api/v1/work-periods/{$this->workPeriodA->id}/schedule");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.work_period.id', $this->workPeriodA->id)
            ->assertJsonPath('data.version.id', $this->versionA->id)
            ->assertJsonPath('data.version.lock_version', 1)
            ->assertJsonCount(7, 'data.days')
            ->assertJsonCount(2, 'data.employees');
    }

    // TEST 2: Usuario autorizado consulta la malla por ID de versión
    public function test_authorized_user_can_fetch_schedule_grid_by_version(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $response = $this->getJson("/api/v1/schedule-versions/{$this->versionA->id}/grid");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version.id', $this->versionA->id);
    }

    // TEST 3: Usuario no autenticado / no autorizado recibe 401
    public function test_unauthenticated_user_cannot_access_grid(): void
    {
        $response = $this->getJson("/api/v1/work-periods/{$this->workPeriodA->id}/schedule");
        $response->assertUnauthorized();
    }

    // TEST 4: Cross-tenant lectura de WorkPeriod denegado
    public function test_cross_tenant_read_grid_is_blocked(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $resPeriod = $this->getJson("/api/v1/work-periods/{$this->workPeriodB->id}/schedule");
        $resPeriod->assertNotFound();

        $resVersion = $this->getJson("/api/v1/schedule-versions/{$this->versionB->id}/grid");
        $resVersion->assertForbidden();
    }

    // TEST 5: Cross-tenant creación en versión ajena es bloqueada
    public function test_cross_tenant_create_assignment_is_blocked(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionB->id}/assignments", $payload);
        $response->assertForbidden();
    }

    // TEST 6: Cross-tenant modificación de asignación ajena es bloqueada
    public function test_cross_tenant_update_assignment_is_blocked(): void
    {
        $assignmentB = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionB->id,
            'employee_id'         => $this->employeeB->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->putJson("/api/v1/schedule-versions/{$this->versionB->id}/assignments/{$assignmentB->id}", $payload);
        $response->assertForbidden();
    }

    // TEST 7: Cross-tenant eliminación de asignación ajena es bloqueada
    public function test_cross_tenant_delete_assignment_is_blocked(): void
    {
        $assignmentB = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionB->id,
            'employee_id'         => $this->employeeB->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->deleteJson("/api/v1/schedule-versions/{$this->versionB->id}/assignments/{$assignmentB->id}", [
            'lock_version' => 1,
        ]);
        $response->assertForbidden();
    }

    // TEST 8: company_id enviado en payload es ignorado / no aceptado
    public function test_company_id_in_payload_is_ignored_and_derived_from_actor(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'company_id'    => $this->companyB->id, // Intento de spoofing
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertOk();

        // Verificar que la asignación guardada pertenece al schedule_version de Company A
        $this->assertDatabaseHas('schedule_assignments', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-25',
        ]);
    }

    // TEST 9: Employee de otro tenant es rechazado (422)
    public function test_employee_from_another_tenant_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeB->id, // Empresa B
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id']);
    }

    // TEST 10: ShiftType de otro tenant es rechazado (422)
    public function test_shift_type_from_another_tenant_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftB->id, // Empresa B
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_type_id']);
    }

    // TEST 11: ScheduleVersion de otro tenant es rechazada
    public function test_schedule_version_from_another_tenant_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $response = $this->getJson("/api/v1/schedule-versions/{$this->versionB->id}/assignments");
        $response->assertForbidden();
    }

    // TEST 12: Fecha antes del WorkPeriod es rechazada (422)
    public function test_assignment_date_before_work_period_start_date_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-23', // Inicia 2026-08-24
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    // TEST 13: Fecha después del WorkPeriod es rechazada (422)
    public function test_assignment_date_after_work_period_end_date_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-31', // Termina 2026-08-30
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    // TEST 14: Fecha límite start_date es aceptada
    public function test_assignment_boundary_start_date_is_accepted(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-24',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertOk();
    }

    // TEST 15: Fecha límite end_date es aceptada
    public function test_assignment_boundary_end_date_is_accepted(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-30',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertOk();
    }

    // TEST 16: Employee de departamento incorrecto es rechazado cuando periodo está acotado
    public function test_employee_from_different_department_is_rejected_when_period_is_scoped(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeADept2->id, // Pertenece a Dept 2 (Finanzas), no a Dept A (Operaciones)
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_id']);
    }

    // TEST 17: Turno inactivo es rechazado
    public function test_inactive_shift_type_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftInactiveA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_type_id']);
    }

    // TEST 18: Creación autorizada calcula timestamps de turno diurno
    public function test_authorized_creation_of_day_shift_calculates_timestamps(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertOk();

        $this->assertEquals(8.0, (float) $response->json('data.assignment.total_hours'));
        $startsAt = $response->json('data.assignment.starts_at');
        $this->assertStringStartsWith('2026-08-25T08:00:00', $startsAt);
    }

    // TEST 19: Turno nocturno que cruza medianoche calcula ends_at en día D+1
    public function test_night_shift_crossing_midnight_calculates_timestamps_plus_one_day(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA2->id,
            'date'          => '2026-08-26',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftNightA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertOk();

        $assignment = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA2->id)
            ->where('date', '2026-08-26')
            ->first();

        $this->assertNotNull($assignment);
        $this->assertEquals('2026-08-26 22:00:00', $assignment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-27 06:00:00', $assignment->ends_at->format('Y-m-d H:i:s'));
    }

    // TEST 20: Turno 24 horas calcula duración exacta
    public function test_24_hour_shift_calculates_correct_duration_and_timestamps(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-24',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shift24hA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertOk();

        $assignment = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();

        $this->assertNotNull($assignment);
        $this->assertEquals('2026-08-24 08:00:00', $assignment->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-25 08:00:00', $assignment->ends_at->format('Y-m-d H:i:s'));
        $this->assertEquals(22.0, (float) $assignment->total_hours);
    }

    // TEST 21: Duplicado de celda lógica actualiza registro existente sin crear duplicados
    public function test_duplicate_assignment_updates_existing_cell_cleanly(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload1 = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload1)->assertOk();

        $payload2 = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'REST',
            'shift_type_id' => null,
            'lock_version'  => 2,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload2)->assertOk();

        // Debe haber exactamente 1 registro para empA1 en 2026-08-25
        $count = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-25')
            ->count();

        $this->assertEquals(1, $count);
    }

    // TEST 22: WorkPeriod CLOSED bloquea creación
    public function test_work_period_closed_blocks_creation(): void
    {
        $this->workPeriodA->update(['status' => WorkPeriodStatus::CLOSED]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertForbidden();
    }

    // TEST 23: WorkPeriod CLOSED bloquea modificación
    public function test_work_period_closed_blocks_modification(): void
    {
        $assignment = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftDayA->id,
        ]);

        $this->workPeriodA->update(['status' => WorkPeriodStatus::CLOSED]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'REST',
            'lock_version'  => 1,
        ];

        $response = $this->putJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments/{$assignment->id}", $payload);
        $response->assertForbidden();
    }

    // TEST 24: WorkPeriod CLOSED bloquea eliminación
    public function test_work_period_closed_blocks_deletion(): void
    {
        $assignment = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftDayA->id,
        ]);

        $this->workPeriodA->update(['status' => WorkPeriodStatus::CLOSED]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->deleteJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments/{$assignment->id}", [
            'lock_version' => 1,
        ]);
        $response->assertForbidden();
    }

    // TEST 25: Versión PUBLISHED bloquea mutaciones
    public function test_version_published_blocks_mutations(): void
    {
        $this->versionA->update(['status' => ScheduleVersionStatus::PUBLISHED]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);
        $response->assertForbidden();
    }

    // TEST 26: lock_version correcto permite operación e incrementa versión
    public function test_correct_lock_version_allows_operation_and_increments_lock(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);

        $response->assertOk()
            ->assertJsonPath('data.lock_version', 2);

        $this->assertEquals(2, $this->versionA->fresh()->lock_version);
    }

    // TEST 27: lock_version obsoleto devuelve 409 Conflict y aborta mutación
    public function test_stale_lock_version_returns_409_conflict_and_aborts_mutation(): void
    {
        $this->versionA->update(['lock_version' => 5]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-25',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 4, // Desactualizado
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload);

        $response->assertStatus(409)
            ->assertJsonPath('current_lock_version', 5);

        $this->assertEquals(5, $this->versionA->fresh()->lock_version);
    }

    // TEST 28: Eliminación autorizada con lock_version remueve asignación
    public function test_authorized_deletion_with_lock_version_removes_assignment(): void
    {
        $assignment = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-25',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftDayA->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->deleteJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments/{$assignment->id}", [
            'lock_version' => 1,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.lock_version', 2);

        $this->assertDatabaseMissing('schedule_assignments', [
            'id' => $assignment->id,
        ]);
    }

    // TEST 29: Registro de auditoría en creación, modificación y eliminación
    public function test_schedule_mutations_generate_audit_logs(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-27',
            'day_type'      => 'WORK',
            'shift_type_id' => $this->shiftDayA->id,
            'lock_version'  => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/assignments", $payload)->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrUserA->id,
            'auditable_type' => ScheduleAssignment::class,
            'action'         => 'UPDATE',
        ]);
    }
}
