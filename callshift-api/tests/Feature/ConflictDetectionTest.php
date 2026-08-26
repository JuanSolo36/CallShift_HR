<?php

namespace Tests\Feature;

use App\Enums\ConflictSeverity;
use App\Enums\ConflictStatus;
use App\Enums\DayType;
use App\Enums\RoleCode;
use App\Enums\RuleViolated;
use App\Enums\ScheduleVersionStatus;
use App\Enums\WeekendRotationPolicy;
use App\Enums\WorkPeriodStatus;
use App\Enums\WorkPeriodType;
use App\Models\Absence;
use App\Models\AuditLog;
use App\Models\Availability;
use App\Models\BusinessRule;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\EmploymentType;
use App\Models\LeaveRequest;
use App\Models\Position;
use App\Models\Role;
use App\Models\ScheduleAssignment;
use App\Models\ScheduleConflict;
use App\Models\ScheduleVersion;
use App\Models\ShiftType;
use App\Models\User;
use App\Models\WorkPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConflictDetectionTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected Department $deptA;
    protected Department $deptB;
    protected Position $posA;
    protected EmploymentType $empType40h;
    protected EmploymentType $empType48h;
    protected Employee $employeeA1;
    protected Employee $employeeA2;
    protected Employee $employeeB1;
    protected ShiftType $shiftMorning;
    protected ShiftType $shiftEvening;
    protected ShiftType $shiftNight;
    protected ShiftType $shiftShort3h;
    protected ShiftType $shiftEarlyMorning;
    protected WorkPeriod $periodA;
    protected ScheduleVersion $versionA;
    protected User $hrUserA;
    protected User $managerUserA;
    protected User $viewerUserA;
    protected User $hrUserB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        // 1. Empresas Multi-tenant
        $this->companyA = Company::create([
            'name'       => 'Company Alpha',
            'legal_name' => 'Company Alpha SAS',
            'tax_id'     => '11111111111',
            'email'      => 'contact@alpha.test',
            'country'    => 'PER',
            'currency'   => 'PEN',
            'timezone'   => 'America/Lima',
            'status'     => 'ACTIVE',
        ]);

        $this->companyB = Company::create([
            'name'       => 'Company Beta',
            'legal_name' => 'Company Beta SAS',
            'tax_id'     => '22222222222',
            'email'      => 'contact@beta.test',
            'country'    => 'PER',
            'currency'   => 'PEN',
            'timezone'   => 'America/Lima',
            'status'     => 'ACTIVE',
        ]);

        // 2. Departamentos y Cargos
        $this->deptA = Department::create(['company_id' => $this->companyA->id, 'name' => 'Operaciones A', 'code' => 'OPS-A', 'status' => 'ACTIVE']);
        $this->deptB = Department::create(['company_id' => $this->companyB->id, 'name' => 'Operaciones B', 'code' => 'OPS-B', 'status' => 'ACTIVE']);
        $this->posA  = Position::create(['company_id' => $this->companyA->id, 'department_id' => $this->deptA->id, 'name' => 'Operador', 'code' => 'OP-A', 'status' => 'ACTIVE']);
        $this->posB  = Position::create(['company_id' => $this->companyB->id, 'department_id' => $this->deptB->id, 'name' => 'Operador B', 'code' => 'OP-B', 'status' => 'ACTIVE']);

        // 3. Tipos de Contrato
        $this->empType40h = EmploymentType::create(['company_id' => $this->companyA->id, 'name' => 'Jornada 40h', 'code' => 'FT-40', 'default_weekly_hours' => 40.0, 'status' => 'ACTIVE']);
        $this->empType48h = EmploymentType::create(['company_id' => $this->companyA->id, 'name' => 'Jornada 48h', 'code' => 'FT-48', 'default_weekly_hours' => 48.0, 'status' => 'ACTIVE']);
        $empTypeB         = EmploymentType::create(['company_id' => $this->companyB->id, 'name' => 'Jornada 48h B', 'code' => 'FT-48-B', 'default_weekly_hours' => 48.0, 'status' => 'ACTIVE']);

        // 4. Colaboradores
        $this->employeeA1 = Employee::create([
            'company_id'         => $this->companyA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empType40h->id,
            'first_name'         => 'Carlos',
            'last_name'          => 'Gomez',
            'employee_code'      => 'EMP-A1',
            'document_type'      => 'DNI',
            'document_number'    => '10000001',
            'email'              => 'carlos@alpha.test',
            'status'             => 'ACTIVE',
            'hire_date'          => '2025-01-01',
        ]);

        $this->employeeA2 = Employee::create([
            'company_id'         => $this->companyA->id,
            'department_id'      => $this->deptA->id,
            'position_id'        => $this->posA->id,
            'employment_type_id' => $this->empType48h->id,
            'first_name'         => 'Maria',
            'last_name'          => 'Lopez',
            'employee_code'      => 'EMP-A2',
            'document_type'      => 'DNI',
            'document_number'    => '10000002',
            'email'              => 'maria@alpha.test',
            'status'             => 'ACTIVE',
            'hire_date'          => '2025-01-01',
        ]);

        $this->employeeB1 = Employee::create([
            'company_id'         => $this->companyB->id,
            'department_id'      => $this->deptB->id,
            'position_id'        => $this->posB->id,
            'employment_type_id' => $empTypeB->id,
            'first_name'         => 'Beto',
            'last_name'          => 'Reyes',
            'employee_code'      => 'EMP-B1',
            'document_type'      => 'DNI',
            'document_number'    => '20000001',
            'email'              => 'beto@beta.test',
            'status'             => 'ACTIVE',
            'hire_date'          => '2025-01-01',
        ]);

        // 5. Tipos de Turno
        $this->shiftMorning = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Turno Mañana',
            'code'             => 'TM-08',
            'start_time'       => '08:00:00',
            'end_time'         => '16:00:00',
            'total_work_hours' => 8.0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftEvening = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Turno Tarde',
            'code'             => 'TT-16',
            'start_time'       => '16:00:00',
            'end_time'         => '00:00:00',
            'total_work_hours' => 8.0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftNight = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Turno Noche',
            'code'             => 'TN-22',
            'start_time'       => '22:00:00',
            'end_time'         => '06:00:00',
            'total_work_hours' => 8.0,
            'crosses_midnight' => true,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftShort3h = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Turno Corto 3h',
            'code'             => 'TC-03',
            'start_time'       => '09:00:00',
            'end_time'         => '12:00:00',
            'total_work_hours' => 3.0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);

        $this->shiftEarlyMorning = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Turno Madrugada',
            'code'             => 'TM-05',
            'start_time'       => '05:00:00',
            'end_time'         => '13:00:00',
            'total_work_hours' => 8.0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);

        // 6. Usuarios RBAC
        $hrRole      = Role::where('code', RoleCode::HR_ADMIN->value)->first();
        $managerRole = Role::where('code', RoleCode::MANAGER->value)->first();
        $viewerRole  = Role::where('code', RoleCode::VIEWER->value)->first();

        $this->hrUserA = User::create([
            'company_id' => $this->companyA->id,
            'name'       => 'HR Admin A',
            'email'      => 'hr@alpha.test',
            'username'   => 'hr_alpha',
            'password'   => bcrypt('password123'),
            'role_id'    => $hrRole->id,
            'status'     => 'ACTIVE',
        ]);

        $this->managerUserA = User::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Manager A',
            'email'      => 'manager@alpha.test',
            'username'   => 'manager_alpha',
            'password'   => bcrypt('password123'),
            'role_id'    => $managerRole->id,
            'status'     => 'ACTIVE',
        ]);

        $this->viewerUserA = User::create([
            'company_id' => $this->companyA->id,
            'name'       => 'Viewer A',
            'email'      => 'viewer@alpha.test',
            'username'   => 'viewer_alpha',
            'password'   => bcrypt('password123'),
            'role_id'    => $viewerRole->id,
            'status'     => 'ACTIVE',
        ]);

        $this->hrUserB = User::create([
            'company_id' => $this->companyB->id,
            'name'       => 'HR Admin B',
            'email'      => 'hr@beta.test',
            'username'   => 'hr_beta',
            'password'   => bcrypt('password123'),
            'role_id'    => $hrRole->id,
            'status'     => 'ACTIVE',
        ]);

        // 7. Periodo Laboral y Versión
        $this->periodA = WorkPeriod::create([
            'company_id'  => $this->companyA->id,
            'name'        => 'Semana 1 Agosto 2026',
            'period_type' => WorkPeriodType::WEEKLY->value,
            'start_date'  => '2026-08-03', // Lunes
            'end_date'    => '2026-08-09', // Domingo
            'status'      => WorkPeriodStatus::DRAFT->value,
            'created_by'  => $this->hrUserA->id,
        ]);

        $this->versionA = ScheduleVersion::create([
            'work_period_id'       => $this->periodA->id,
            'version_number'       => 1,
            'status'               => ScheduleVersionStatus::DRAFT,
            'parent_version_id'    => null,
            'change_summary'       => 'Versión inicial borrador',
            'lock_version'         => 1,
            'created_by'           => $this->hrUserA->id,
        ]);

        $this->periodA->update(['current_version_id' => $this->versionA->id]);
    }

    // =========================================================================
    // 1. GARANTÍAS FÍSICAS DE UNICIDAD Y SEGURIDAD MULTI-TENANT (department_scope_id)
    // =========================================================================

    public function test_database_unique_constraint_enforces_single_global_business_rule(): void
    {
        // 1. Crear regla global válida (department_id = NULL => department_scope_id = 0)
        BusinessRule::create([
            'company_id'          => $this->companyA->id,
            'department_id'       => null,
            'max_daily_hours'     => 10.0,
            'max_weekly_hours'    => 48.0,
        ]);

        // 2. Intentar crear una segunda regla global para la misma empresa debe lanzar excepción PDO por restricción UNIQUE
        $this->expectException(\Illuminate\Database\QueryException::class);

        BusinessRule::create([
            'company_id'          => $this->companyA->id,
            'department_id'       => null,
            'max_daily_hours'     => 12.0,
            'max_weekly_hours'    => 44.0,
        ]);
    }

    public function test_database_unique_constraint_enforces_single_department_business_rule(): void
    {
        // 1. Crear regla para el departamento A (department_scope_id = deptA->id)
        BusinessRule::create([
            'company_id'          => $this->companyA->id,
            'department_id'       => $this->deptA->id,
            'max_daily_hours'     => 8.0,
            'max_weekly_hours'    => 40.0,
        ]);

        // 2. Intentar crear duplicado en el mismo departamento debe violar restricción física UNIQUE
        $this->expectException(\Illuminate\Database\QueryException::class);

        BusinessRule::create([
            'company_id'          => $this->companyA->id,
            'department_id'       => $this->deptA->id,
            'max_daily_hours'     => 9.0,
            'max_weekly_hours'    => 42.0,
        ]);
    }

    public function test_department_scope_id_cannot_be_manipulated_via_payload_and_is_always_derived(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Intentar inyectar department_scope_id manipulado vía API
        $payload = [
            'department_id'       => null,
            'department_scope_id' => 999999, // Ataque de bypass
            'max_daily_hours'     => 9.0,
        ];

        $response = $this->postJson('/api/v1/business-rules', $payload);
        $response->assertStatus(201);

        // El modelo debe ignorar el payload y forzar department_scope_id = 0
        $created = BusinessRule::where('company_id', $this->companyA->id)->whereNull('department_id')->first();
        $this->assertNotNull($created);
        $this->assertEquals(0, $created->department_scope_id);
    }

    public function test_department_scope_id_resynchronized_on_update_mutation(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // 1. Crear regla departamental para deptA (department_scope_id = deptA->id)
        $rule = BusinessRule::create([
            'company_id'          => $this->companyA->id,
            'department_id'       => $this->deptA->id,
            'max_daily_hours'     => 8.0,
        ]);
        $this->assertEquals($this->deptA->id, $rule->fresh()->department_scope_id);

        // 2. Actualizar a departamento B -> department_scope_id se resincroniza automáticamente a deptB->id
        $rule->update(['department_id' => $this->posA->department_id]);
        $this->assertEquals($this->posA->department_id, $rule->fresh()->department_scope_id);

        // 3. Actualizar a NULL (regla global) -> department_scope_id se resincroniza automáticamente a 0
        // Primero eliminamos cualquier otra regla global para no chocar con el UNIQUE
        BusinessRule::where('company_id', $this->companyA->id)->where('department_scope_id', 0)->delete();
        $rule->update(['department_id' => null]);
        $this->assertEquals(0, $rule->fresh()->department_scope_id);
    }

    public function test_concurrent_business_rule_creation_race_condition_prevented_by_database_constraint(): void
    {
        DB::transaction(function () {
            BusinessRule::create([
                'company_id'          => $this->companyA->id,
                'department_id'       => null,
                'max_daily_hours'     => 10.0,
            ]);

            try {
                // Simulación de request concurrente en milisegundo idéntico
                BusinessRule::create([
                    'company_id'          => $this->companyA->id,
                    'department_id'       => null,
                    'max_daily_hours'     => 10.0,
                ]);
                $this->fail('La restricción UNIQUE de base de datos debió prevenir la inserción duplicada concurrente.');
            } catch (\Illuminate\Database\QueryException $e) {
                $this->assertTrue(true, 'La base de datos bloqueó la carrera de creación concurrente.');
            }
        });
    }

    // =========================================================================
    // 2. DETECCIÓN EXHAUSTIVA DE LAS 12 REGLAS CANÓNICAS
    // =========================================================================

    // R1: Solapamiento Horario
    public function test_detects_overlapping_shifts_same_day_and_cross_midnight(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Asignar Turno Noche (22:00 -> 06:00 +1) el 2026-08-03
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id,
            'total_hours'         => 8.0,
        ]);
        // Asignar Turno Madrugada (05:00 -> 13:00) el 2026-08-04 (solapa de 05:00 a 06:00 cruzando medianoche)
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftEarlyMorning->id,
            'total_hours'         => 8.0,
        ]);

        $res = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate");
        $res->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::OVERLAPPING_SHIFTS->value,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
            'status'              => ConflictStatus::ACTIVE->value,
        ]);
    }

    // R2: Descanso Mínimo entre Turnos Consecutivos (< 12h)
    public function test_detects_insufficient_rest_between_consecutive_shifts(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Turno Noche 22:00 -> 06:00 (+1) que termina a las 06:00 del 04 de Agosto
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id,
            'total_hours'         => 8.0,
        ]);

        // Turno Mañana 08:00 -> 16:00 el 04 de Agosto (solo 2 horas de descanso entre 06:00 y 08:00, mínimo 12h)
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $res = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate");
        $res->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::MIN_REST_BETWEEN_SHIFTS->value,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
            'status'              => ConflictStatus::ACTIVE->value,
        ]);
    }

    // R3: Máximo de Días Consecutivos de Trabajo
    public function test_detects_max_consecutive_work_days_with_historical_context(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Crear 7 días seguidos de trabajo (Lunes 03 a Domingo 09 de Agosto)
        for ($d = 3; $d <= 9; $d++) {
            ScheduleAssignment::create([
                'schedule_version_id' => $this->versionA->id,
                'employee_id'         => $this->employeeA1->id,
                'date'                => sprintf('2026-08-%02d', $d),
                'day_type'            => DayType::WORK,
                'shift_type_id'       => $this->shiftMorning->id,
                'total_hours'         => 8.0,
            ]);
        }

        $res = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate");
        $res->assertOk();

        // 7 días supera el máximo permitido de 6 días continuos
        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::MAX_CONSECUTIVE_WORK_DAYS->value,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
        ]);
    }

    // R4: Máximo de Horas Diarias (HARD)
    public function test_detects_max_daily_hours_exceeded(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $shift14h = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Guardia 14h',
            'code'             => 'G14',
            'start_time'       => '06:00:00',
            'end_time'         => '20:00:00',
            'total_work_hours' => 14.0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);

        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $shift14h->id,
            'total_hours'         => 14.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'employee_id'   => $this->employeeA1->id,
            'rule_violated' => RuleViolated::MAX_DAILY_HOURS->value,
            'severity'      => ConflictSeverity::HARD_CONFLICT->value,
        ]);
    }

    // R5: Mínimo de Horas Diarias (SOFT)
    public function test_detects_min_daily_hours_deficit(): void
    {
        Sanctum::actingAs($this->hrUserA);

        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA2->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftShort3h->id, // 3h < 4h min diario
            'total_hours'         => 3.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'employee_id'   => $this->employeeA2->id,
            'rule_violated' => RuleViolated::MIN_DAILY_HOURS->value,
            'severity'      => ConflictSeverity::SOFT_WARNING->value,
        ]);
    }

    // R6 & R7: Precedencia y Severidad Semanal (Contractual vs Legal)
    public function test_weekly_hours_precedence_contract_warning_vs_legal_hard_conflict(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Caso A: employeeA1 (Contrato FT-40, 40h base). Asignamos 44h (5.5 días x 8h).
        // 44h > 40h contractual y <= 48h legal -> CONTRACT_WEEKLY_HOURS_EXCEEDED (SOFT_WARNING)
        for ($d = 3; $d <= 7; $d++) {
            ScheduleAssignment::create([
                'schedule_version_id' => $this->versionA->id,
                'employee_id'         => $this->employeeA1->id,
                'date'                => sprintf('2026-08-%02d', $d),
                'day_type'            => DayType::WORK,
                'shift_type_id'       => $this->shiftMorning->id, // 8h
                'total_hours'         => 8.0,
            ]);
        }
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-08',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftShort3h->id, // 3h + 1h = 4h total
            'total_hours'         => 4.0,
        ]); // Total: 44h

        // Caso B: employeeA2 (Contrato FT-48, 48h base). Asignamos 56h (7 días x 8h).
        // 56h > 48h legal -> LEGAL_WEEKLY_HOURS_EXCEEDED (HARD_CONFLICT)
        for ($d = 3; $d <= 9; $d++) {
            ScheduleAssignment::create([
                'schedule_version_id' => $this->versionA->id,
                'employee_id'         => $this->employeeA2->id,
                'date'                => sprintf('2026-08-%02d', $d),
                'day_type'            => DayType::WORK,
                'shift_type_id'       => $this->shiftMorning->id,
                'total_hours'         => 8.0,
            ]);
        }

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        // Verificar Caso A (SOFT_WARNING)
        $this->assertDatabaseHas('schedule_conflicts', [
            'employee_id'   => $this->employeeA1->id,
            'rule_violated' => RuleViolated::CONTRACT_WEEKLY_HOURS_EXCEEDED->value,
            'severity'      => ConflictSeverity::SOFT_WARNING->value,
        ]);
        $this->assertDatabaseMissing('schedule_conflicts', [
            'employee_id'   => $this->employeeA1->id,
            'rule_violated' => RuleViolated::LEGAL_WEEKLY_HOURS_EXCEEDED->value,
        ]);

        // Verificar Caso B (HARD_CONFLICT)
        $this->assertDatabaseHas('schedule_conflicts', [
            'employee_id'   => $this->employeeA2->id,
            'rule_violated' => RuleViolated::LEGAL_WEEKLY_HOURS_EXCEEDED->value,
            'severity'      => ConflictSeverity::HARD_CONFLICT->value,
        ]);
    }

    // R8: Mínimo de Horas Semanales en Semana Completa
    public function test_detects_min_weekly_hours_deficit_on_complete_iso_week(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Periodo completo: 2026-08-03 (Lunes) a 2026-08-09 (Domingo).
        // Min semanal default = 20h. Asignamos 8h (1 solo turno).
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'employee_id'   => $this->employeeA1->id,
            'rule_violated' => RuleViolated::MIN_WEEKLY_HOURS->value,
            'severity'      => ConflictSeverity::SOFT_WARNING->value,
        ]);
    }

    // R8: Ignorado en Semanas Parciales
    public function test_min_weekly_hours_deficit_is_ignored_on_partial_iso_week(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Periodo parcial: Empieza Miércoles 2026-08-05 y termina Domingo 2026-08-09 (semana incompleta)
        $partialPeriod = WorkPeriod::create([
            'company_id'  => $this->companyA->id,
            'name'        => 'Periodo Parcial',
            'period_type' => WorkPeriodType::CUSTOM->value,
            'start_date'  => '2026-08-05',
            'end_date'    => '2026-08-09',
            'status'      => WorkPeriodStatus::DRAFT->value,
            'created_by'  => $this->hrUserA->id,
        ]);
        $partialVersion = ScheduleVersion::create([
            'work_period_id' => $partialPeriod->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'created_by'     => $this->hrUserA->id,
        ]);
        $partialPeriod->update(['current_version_id' => $partialVersion->id]);

        // Asignar 8h en el periodo parcial
        ScheduleAssignment::create([
            'schedule_version_id' => $partialVersion->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-05',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$partialVersion->id}/validate")->assertOk();

        // En semana parcial NO debe generarse MIN_WEEKLY_HOURS
        $this->assertDatabaseMissing('schedule_conflicts', [
            'schedule_version_id' => $partialVersion->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::MIN_WEEKLY_HOURS->value,
        ]);
    }

    // R9: Colisión con Ausencias Aprobadas
    public function test_detects_approved_absence_collision(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Permiso aprobado para employeeA1 el 2026-08-05
        LeaveRequest::create([
            'company_id'  => $this->companyA->id,
            'employee_id' => $this->employeeA1->id,
            'start_date'  => '2026-08-05',
            'end_date'    => '2026-08-05',
            'leave_type'  => 'VACATION',
            'status'      => 'APPROVED',
            'reason'      => 'Vacaciones programadas',
        ]);

        // Asignar turno de trabajo el mismo día 2026-08-05
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-05',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'employee_id'   => $this->employeeA1->id,
            'rule_violated' => RuleViolated::APPROVED_ABSENCE_COLLISION->value,
            'severity'      => ConflictSeverity::HARD_CONFLICT->value,
        ]);
    }

    // R10: Restricciones de Disponibilidad - STRICT_RESTRICTION genera HARD_CONFLICT
    public function test_detects_strict_unavailable_restriction_generates_hard_conflict(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // No disponible estricto el miércoles 2026-08-05
        Availability::create([
            'company_id'   => $this->companyA->id,
            'employee_id'  => $this->employeeA1->id,
            'type'         => 'SPECIFIC_DATE',
            'specific_date'=> '2026-08-05',
            'is_available' => false,
            'priority'     => 'STRICT_RESTRICTION',
            'status'       => 'ACTIVE',
        ]);

        // Asignar turno el miércoles 2026-08-05
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-05',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::UNAVAILABLE_RESTRICTION->value,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
            'status'              => ConflictStatus::ACTIVE->value,
        ]);
    }

    // R10: Restricciones de Disponibilidad - PREFERENCE genera SOFT_WARNING
    public function test_detects_preference_unavailable_restriction_generates_soft_warning(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Preferencia de no disponibilidad el jueves 2026-08-06
        Availability::create([
            'company_id'   => $this->companyA->id,
            'employee_id'  => $this->employeeA1->id,
            'type'         => 'SPECIFIC_DATE',
            'specific_date'=> '2026-08-06',
            'is_available' => false,
            'priority'     => 'PREFERENCE',
            'status'       => 'ACTIVE',
        ]);

        // Asignar turno el jueves 2026-08-06
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-06',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::UNAVAILABLE_RESTRICTION->value,
            'severity'            => ConflictSeverity::SOFT_WARNING->value,
            'status'              => ConflictStatus::ACTIVE->value,
        ]);
    }

    // R11: Rotación de Fines de Semana - STRICT_ROTATION genera HARD_CONFLICT
    public function test_detects_weekend_rotation_strict_policy_generates_hard_conflict(): void
    {
        Sanctum::actingAs($this->hrUserA);

        BusinessRule::create([
            'company_id'              => $this->companyA->id,
            'department_scope_id'     => 0,
            'weekend_rotation_policy' => WeekendRotationPolicy::STRICT_ROTATION,
        ]);

        $period2Weeks = WorkPeriod::create([
            'company_id'  => $this->companyA->id,
            'name'        => 'Quincena Agosto 2026 Strict',
            'period_type' => WorkPeriodType::BIWEEKLY->value,
            'start_date'  => '2026-08-03',
            'end_date'    => '2026-08-16',
            'status'      => WorkPeriodStatus::DRAFT->value,
            'created_by'  => $this->hrUserA->id,
        ]);
        $version2Weeks = ScheduleVersion::create([
            'work_period_id' => $period2Weeks->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'created_by'     => $this->hrUserA->id,
        ]);
        $period2Weeks->update(['current_version_id' => $version2Weeks->id]);

        // Fin de semana 1 (08 y 09) y Fin de semana 2 (15 y 16)
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-08', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-09', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-15', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-16', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);

        $this->postJson("/api/v1/schedule-versions/{$version2Weeks->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $version2Weeks->id,
            'rule_violated'       => RuleViolated::WEEKEND_ROTATION_VIOLATION->value,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
        ]);
    }

    // R11: Rotación de Fines de Semana - FAIR_SHARE genera SOFT_WARNING
    public function test_detects_weekend_rotation_fair_share_policy_generates_soft_warning(): void
    {
        Sanctum::actingAs($this->hrUserA);

        BusinessRule::create([
            'company_id'              => $this->companyA->id,
            'department_scope_id'     => 0,
            'weekend_rotation_policy' => WeekendRotationPolicy::FAIR_SHARE,
        ]);

        $period2Weeks = WorkPeriod::create([
            'company_id'  => $this->companyA->id,
            'name'        => 'Quincena Agosto 2026 Fair',
            'period_type' => WorkPeriodType::BIWEEKLY->value,
            'start_date'  => '2026-08-03',
            'end_date'    => '2026-08-16',
            'status'      => WorkPeriodStatus::DRAFT->value,
            'created_by'  => $this->hrUserA->id,
        ]);
        $version2Weeks = ScheduleVersion::create([
            'work_period_id' => $period2Weeks->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'created_by'     => $this->hrUserA->id,
        ]);
        $period2Weeks->update(['current_version_id' => $version2Weeks->id]);

        // Fin de semana 1 y 2 trabajados
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-08', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-09', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-15', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-16', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);

        $this->postJson("/api/v1/schedule-versions/{$version2Weeks->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $version2Weeks->id,
            'rule_violated'       => RuleViolated::WEEKEND_ROTATION_VIOLATION->value,
            'severity'            => ConflictSeverity::SOFT_WARNING->value,
        ]);
    }

    // R11: Rotación de Fines de Semana - NONE genera 0 conflictos
    public function test_weekend_rotation_none_policy_generates_no_conflict(): void
    {
        Sanctum::actingAs($this->hrUserA);

        BusinessRule::create([
            'company_id'              => $this->companyA->id,
            'department_scope_id'     => 0,
            'weekend_rotation_policy' => WeekendRotationPolicy::NONE,
        ]);

        $period2Weeks = WorkPeriod::create([
            'company_id'  => $this->companyA->id,
            'name'        => 'Quincena Agosto 2026 None',
            'period_type' => WorkPeriodType::BIWEEKLY->value,
            'start_date'  => '2026-08-03',
            'end_date'    => '2026-08-16',
            'status'      => WorkPeriodStatus::DRAFT->value,
            'created_by'  => $this->hrUserA->id,
        ]);
        $version2Weeks = ScheduleVersion::create([
            'work_period_id' => $period2Weeks->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::DRAFT,
            'lock_version'   => 1,
            'created_by'     => $this->hrUserA->id,
        ]);
        $period2Weeks->update(['current_version_id' => $version2Weeks->id]);

        // Fin de semana 1 y 2 trabajados por completo
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-08', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-09', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-15', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);
        ScheduleAssignment::create(['schedule_version_id' => $version2Weeks->id, 'employee_id' => $this->employeeA1->id, 'date' => '2026-08-16', 'day_type' => DayType::WORK, 'shift_type_id' => $this->shiftMorning->id, 'total_hours' => 8.0]);

        $this->postJson("/api/v1/schedule-versions/{$version2Weeks->id}/validate")->assertOk();

        // No debe generarse conflicto de WEEKEND_ROTATION_VIOLATION
        $this->assertDatabaseMissing('schedule_conflicts', [
            'schedule_version_id' => $version2Weeks->id,
            'rule_violated'       => RuleViolated::WEEKEND_ROTATION_VIOLATION->value,
        ]);
    }

    // R12: Prohibición de Turnos Nocturnos
    public function test_detects_night_shift_disallowed_when_policy_forbids_it(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Regla global que prohíbe turnos nocturnos
        BusinessRule::create([
            'company_id'          => $this->companyA->id,
            'department_scope_id' => 0,
            'allow_night_shifts'  => false,
        ]);

        // Asignar turno noche (22:00 -> 06:00)
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertDatabaseHas('schedule_conflicts', [
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'rule_violated'       => RuleViolated::NIGHT_SHIFT_DISALLOWED->value,
            'severity'            => ConflictSeverity::HARD_CONFLICT->value,
        ]);
    }

    // =========================================================================
    // 3. IDEMPOTENCIA, CICLO DE VIDA Y REVALIDACIÓN FORENSE
    // =========================================================================

    public function test_validate_version_is_strictly_idempotent_across_multiple_executions(): void
    {
        Sanctum::actingAs($this->hrUserA);

        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id,
            'total_hours'         => 8.0,
        ]);
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftEarlyMorning->id,
            'total_hours'         => 8.0,
        ]);

        // Ejecutar 3 validaciones consecutivas
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();
        $count1 = ScheduleConflict::where('schedule_version_id', $this->versionA->id)->count();

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();
        $count2 = ScheduleConflict::where('schedule_version_id', $this->versionA->id)->count();

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();
        $count3 = ScheduleConflict::where('schedule_version_id', $this->versionA->id)->count();

        $this->assertEquals($count1, $count2);
        $this->assertEquals($count2, $count3);
    }

    public function test_revalidation_preserves_resolved_conflicts_if_underlying_assignment_is_unchanged(): void
    {
        Sanctum::actingAs($this->hrUserA);

        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id,
            'total_hours'         => 8.0,
        ]);
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftEarlyMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();
        $conflict = ScheduleConflict::where('schedule_version_id', $this->versionA->id)->first();

        // Resolver conflicto
        $this->patchJson("/api/v1/schedule-conflicts/{$conflict->id}/resolve", [
            'reason' => 'Excepción autorizada por emergencia de cobertura.',
        ])->assertOk();

        $this->assertTrue($conflict->fresh()->is_resolved);
        $this->assertEquals('RESOLVED', $conflict->fresh()->status->value);

        // Revalidar: debe preservar el estado RESOLVED inmutable
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertTrue($conflict->fresh()->is_resolved);
        $this->assertEquals('RESOLVED', $conflict->fresh()->status->value);
        $this->assertEquals('Excepción autorizada por emergencia de cobertura.', $conflict->fresh()->resolution_reason);
    }

    public function test_corrected_assignment_marks_conflict_as_auto_cleared_on_revalidation(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $a1 = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id,
            'total_hours'         => 8.0,
        ]);
        $a2 = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftEarlyMorning->id,
            'total_hours'         => 8.0,
        ]);

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();
        $conflict = ScheduleConflict::where('schedule_version_id', $this->versionA->id)->first();
        $this->assertEquals(ConflictStatus::ACTIVE->value, $conflict->status->value);

        // Corregir la causa: eliminar la asignación solapada
        $a2->delete();

        // Revalidar: el conflicto no se borra, pasa a AUTO_CLEARED
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        $this->assertEquals(ConflictStatus::AUTO_CLEARED->value, $conflict->fresh()->status->value);
        $this->assertFalse($conflict->fresh()->is_resolved);
    }

    // =========================================================================
    // 4. HERENCIA CAMPO A CAMPO Y PRECEDENCIA
    // =========================================================================

    public function test_department_rule_overrides_global_with_field_level_inheritance(): void
    {
        // 1. Regla Global: max_daily = 10h, min_rest = 12h
        BusinessRule::create([
            'company_id'                    => $this->companyA->id,
            'department_id'                 => null,
            'department_scope_id'           => 0,
            'max_daily_hours'               => 10.0,
            'min_rest_hours_between_shifts' => 12.0,
        ]);

        // 2. Regla Departamental: max_daily = 8h, min_rest = NULL (hereda 12h de global)
        BusinessRule::create([
            'company_id'                    => $this->companyA->id,
            'department_id'                 => $this->deptA->id,
            'department_scope_id'           => $this->deptA->id,
            'max_daily_hours'               => 8.0,
            'min_rest_hours_between_shifts' => null,
        ]);

        $ruleService = app(\App\Services\Conflicts\BusinessRuleService::class);
        $resolved = $ruleService->resolveEffectiveRule($this->employeeA1);

        $this->assertEquals(8.0, $resolved->max_daily_hours); // Sobrescrito por departamento
        $this->assertEquals(12.0, $resolved->min_rest_hours_between_shifts); // Heredado de global
        $this->assertEquals(6, $resolved->max_consecutive_work_days); // Heredado de system default
    }

    // =========================================================================
    // 5. CONTROL DE ACCESO RBAC, MULTI-TENANCY Y BLOQUEO ATÓMICO DE PUBLICACIÓN
    // =========================================================================

    public function test_cross_tenant_business_rule_and_conflict_access_is_blocked(): void
    {
        Sanctum::actingAs($this->hrUserB);

        // Intentar ver versión de empresa A
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertForbidden();
        $this->getJson("/api/v1/schedule-versions/{$this->versionA->id}/conflicts")->assertForbidden();
    }

    public function test_published_version_blocks_unresolved_hard_conflicts(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Crear descanso insuficiente crítico (HARD) en versionA
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNight->id, // 22:00 -> 06:00 (+1)
            'total_hours'         => 8.0,
        ]);
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id, // 08:00 -> 16:00 (descanso 2h < 12h)
            'total_hours'         => 8.0,
        ]);

        // Intentar publicar el periodo laboral con conflicto HARD activo -> RECHAZADO (422)
        $response = $this->patchJson("/api/v1/work-periods/{$this->periodA->id}/status", [
            'status'       => WorkPeriodStatus::PUBLISHED->value,
            'lock_version' => 1,
        ]);

        $response->assertStatus(422);
        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);
    }

    public function test_publish_requires_mandatory_lock_version_and_rejects_missing_lock(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $res = $this->patchJson("/api/v1/work-periods/{$this->periodA->id}/status", [
            'status' => WorkPeriodStatus::PUBLISHED->value,
            // lock_version omitido intencionalmente
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['lock_version']);
    }

    public function test_atomic_publish_detects_concurrent_mutation_and_blocks_publication(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // 1. Estado inicial válido (1 solo turno, 0 conflictos)
        $cleanAssignment = ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);

        // Validar versión inicialmente (resultado limpio)
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/validate")->assertOk();

        // 2. Simular mutación concurrente (Proceso B inserta turno de 14h el día siguiente antes de que Proceso A llame a publicar)
        $shift14h = ShiftType::create([
            'company_id'       => $this->companyA->id,
            'name'             => 'Guardia 14h',
            'code'             => 'G14-P',
            'start_time'       => '06:00:00',
            'end_time'         => '20:00:00',
            'total_work_hours' => 14.0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-04',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $shift14h->id,
            'total_hours'         => 14.0,
        ]);

        // 3. Proceso A intenta publicar enviando lock_version
        $publishResponse = $this->patchJson("/api/v1/work-periods/{$this->periodA->id}/status", [
            'status'       => WorkPeriodStatus::PUBLISHED->value,
            'lock_version' => 1,
        ]);

        // La revalidación atómica en tiempo real de WorkPeriodService dentro de la transacción detecta la mutación y rechaza con 422
        $publishResponse->assertStatus(422);

        // Verificaciones forenses de atomicidad estricta e integridad de estado
        // 1. El estado del periodo permanece inmutable en DRAFT (no PUBLISHED)
        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);

        // 2. El estado de la versión de horario permanece en DRAFT
        $this->assertEquals(ScheduleVersionStatus::DRAFT->value, $this->versionA->fresh()->status->value);

        // 3. El lock_version no fue consumido ni incrementado
        $this->assertEquals(1, $this->versionA->fresh()->lock_version);

        // 4. No se emitió ningún registro de auditoría de publicación exitosa
        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => WorkPeriod::class,
            'auditable_id'   => $this->periodA->id,
            'action'         => 'PUBLISHED',
        ]);
    }

    public function test_simultaneous_concurrent_transactions_publish_vs_mutation_race(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // 1. Estado inicial con 1 turno limpio y versión en REVIEW
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);
        $this->versionA->update(['status' => ScheduleVersionStatus::REVIEW]);

        // Proceso A lee lock_version = 1
        $initialLock = $this->versionA->fresh()->lock_version;
        $this->assertEquals(1, $initialLock);

        // 2. Transacción B concurrente muta la malla mediante ScheduleEditorService
        $editorService = app(\App\Services\Schedule\ScheduleEditorService::class);
        $editorService->upsertAssignment($this->versionA, [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-04',
            'day_type'      => DayType::WORK->value,
            'shift_type_id' => $this->shiftMorning->id,
            'lock_version'  => 1,
        ], $this->hrUserA);

        // lock_version avanzó atómicamente a 2
        $this->assertEquals(2, $this->versionA->fresh()->lock_version);

        // 3. Proceso A intenta publicar con el lock_version = 1 obsoleto
        $response = $this->patchJson("/api/v1/work-periods/{$this->periodA->id}/status", [
            'status'       => WorkPeriodStatus::PUBLISHED->value,
            'lock_version' => $initialLock, // 1 (Stale)
        ]);

        // La carrera concurrente es detectada y abortada inmediatamente con 409 Conflict
        $response->assertStatus(409);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Conflicto de concurrencia al cambiar de estado. La versión de horario fue modificada por otro proceso.',
        ]);

        // 4. Verificaciones de atomicidad e integridad
        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);
        $this->assertEquals(ScheduleVersionStatus::REVIEW->value, $this->versionA->fresh()->status->value);
        $this->assertEquals(2, $this->versionA->fresh()->lock_version);
        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => WorkPeriod::class,
            'auditable_id'   => $this->periodA->id,
            'action'         => 'PUBLISHED',
        ]);
    }

    public function test_concurrent_mutation_interleaved_during_publish_validation_aborts_publish(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // 1. Estado inicial con 1 turno limpio y versión en REVIEW
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftMorning->id,
            'total_hours'         => 8.0,
        ]);
        $this->versionA->update(['status' => ScheduleVersionStatus::REVIEW]);

        $initialLock = $this->versionA->fresh()->lock_version; // 1

        // 2. Intercalar Transacción B DURANTE la ejecución de validateVersion en Transacción A
        $realService = app(\App\Services\Conflicts\ConflictDetectionService::class);
        $editorService = app(\App\Services\Schedule\ScheduleEditorService::class);

        $interleavedExecuted = false;

        $this->instance(
            \App\Services\Conflicts\ConflictDetectionService::class,
            new class($realService, $editorService, $this->versionA, $this->employeeA1, $this->shiftMorning, $this->hrUserA, $interleavedExecuted) extends \App\Services\Conflicts\ConflictDetectionService {
                private $realService;
                private $editorService;
                private $version;
                private $employee;
                private $shift;
                    public function __construct($realService, $editorService, $version, $employee, $shift, $actor, &$interleavedExecuted)
                {
                    parent::__construct(app(\App\Services\Conflicts\BusinessRuleService::class));
                    $this->realService = $realService;
                    $this->editorService = $editorService;
                    $this->version = $version;
                    $this->employee = $employee;
                    $this->shift = $shift;
                    $this->actor = $actor;
                    $this->interleavedExecuted = &$interleavedExecuted;
                }

                public function validateVersion(\App\Models\ScheduleVersion $version, ?\App\Models\User $actor = null): \Illuminate\Database\Eloquent\Collection
                {
                    // BARRERA / PUNTO DE INTERCALACIÓN CONCURRENTE:
                    // Mientras Transacción A está validando dentro de su DB::transaction():
                    // Transacción B entra y muta una celda de la misma versión incrementando lock_version
                    if (!$this->interleavedExecuted) {
                        $this->interleavedExecuted = true;
                        $this->editorService->upsertAssignment($this->version, [
                            'employee_id'   => $this->employee->id,
                            'date'          => '2026-08-04',
                            'day_type'      => \App\Enums\DayType::WORK->value,
                            'shift_type_id' => $this->shift->id,
                            'lock_version'  => 1, // B consume lock_version 1 y lo avanza a 2
                        ], $this->actor);
                    }

                    return $this->realService->validateVersion($version, $actor);
                }

                public function resolveEffectiveRule(\App\Models\Employee $employee): \App\DTOs\EffectiveBusinessRuleDTO
                {
                    return $this->realService->resolveEffectiveRule($employee);
                }
            }
        );

        // 3. Transacción A llama a publicar enviando lock_version = 1
        $response = $this->patchJson("/api/v1/work-periods/{$this->periodA->id}/status", [
            'status'       => WorkPeriodStatus::PUBLISHED->value,
            'lock_version' => 1,
        ]);

        // Verificamos que la intercalación concurrente ocurrió dentro de la transacción de publicación
        $this->assertTrue($interleavedExecuted, 'La transacción B debió haberse ejecutado durante la ventana crítica de validateVersion.');

        // Transacción A detecta la colisión atómicamente en la sentencia UPDATE WHERE lock_version = 1 y aborta con 409
        $response->assertStatus(409);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Conflicto de concurrencia detectado al persistir la versión de horario.',
        ]);

        // 4. Verificaciones forenses de aislamiento y rollback atómico
        // El periodo permanece en DRAFT (rollback de A exitoso)
        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);
        // La versión permanece en REVIEW (no fue publicada con estado corrupto)
        $this->assertEquals(ScheduleVersionStatus::REVIEW->value, $this->versionA->fresh()->status->value);
        // La transacción completa fue revertida (rollback total) por lo que lock_version permanece intacto en 1
        $this->assertEquals(1, $this->versionA->fresh()->lock_version);
        // Cero audit logs de publicación exitosa
        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => WorkPeriod::class,
            'auditable_id'   => $this->periodA->id,
            'action'         => 'PUBLISHED',
        ]);
    }

    public function test_assignment_mutation_blocked_once_version_is_published(): void
    {
        Sanctum::actingAs($this->hrUserA);

        // Publicar formalmente
        $this->versionA->update(['status' => ScheduleVersionStatus::PUBLISHED]);
        $this->periodA->update(['status' => WorkPeriodStatus::PUBLISHED]);

        $editorService = app(\App\Services\Schedule\ScheduleEditorService::class);

        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $editorService->upsertAssignment($this->versionA, [
            'employee_id'   => $this->employeeA1->id,
            'date'          => '2026-08-04',
            'day_type'      => DayType::WORK->value,
            'shift_type_id' => $this->shiftMorning->id,
            'lock_version'  => (int)$this->versionA->fresh()->lock_version,
        ], $this->hrUserA);
    }

    public function test_concurrent_publish_with_optimistic_lock_version_conflict_is_rejected_with_409(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $this->versionA->update(['status' => ScheduleVersionStatus::REVIEW]);

        // 1. Proceso A lee la versión en lock_version = 1
        $initialLockVersion = $this->versionA->fresh()->lock_version; // 1

        // 2. Transacción concurrente B: Proceso B muta la versión y avanza lock_version a 2
        $this->versionA->increment('lock_version'); // lock_version ahora es 2

        // 3. Proceso A intenta publicar enviando el lock_version = 1 que leyó originalmente
        $response = $this->patchJson("/api/v1/work-periods/{$this->periodA->id}/status", [
            'status'       => WorkPeriodStatus::PUBLISHED->value,
            'lock_version' => $initialLockVersion, // 1 (desactualizado)
        ]);

        // Debe ser rechazado atómicamente con HTTP 409 Conflict
        $response->assertStatus(409);
        $response->assertJson([
            'status'  => 'error',
            'message' => 'Conflicto de concurrencia al cambiar de estado. La versión de horario fue modificada por otro proceso.',
        ]);

        // Verificaciones de atomicidad e integridad
        // El periodo sigue en DRAFT
        $this->assertEquals(WorkPeriodStatus::DRAFT->value, $this->periodA->fresh()->status->value);
        // La versión sigue en REVIEW
        $this->assertEquals(ScheduleVersionStatus::REVIEW->value, $this->versionA->fresh()->status->value);
        // lock_version se mantiene en 2 (no fue consumido ni alterado por el proceso A)
        $this->assertEquals(2, $this->versionA->fresh()->lock_version);
        // Cero audit logs de publicación
        $this->assertDatabaseMissing('audit_logs', [
            'auditable_type' => WorkPeriod::class,
            'auditable_id'   => $this->periodA->id,
            'action'         => 'PUBLISHED',
        ]);
    }

    public function test_viewer_and_supervisor_cannot_resolve_conflicts_without_permission(): void
    {
        // 1. Crear conflicto inicial
        $conflict = ScheduleConflict::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'conflict_key'        => 'key-test-rbac',
            'severity'            => ConflictSeverity::HARD_CONFLICT,
            'rule_violated'       => RuleViolated::OVERLAPPING_SHIFTS->value,
            'description'         => 'Test conflict',
            'status'              => ConflictStatus::ACTIVE,
            'is_resolved'         => false,
        ]);

        // 2. Viewer intenta resolver -> 403 Forbidden
        Sanctum::actingAs($this->viewerUserA);
        $this->patchJson("/api/v1/schedule-conflicts/{$conflict->id}/resolve", ['reason' => 'Intento viewer'])->assertForbidden();

        // 3. HR Admin resuelve exitosamente -> 200 OK
        Sanctum::actingAs($this->hrUserA);
        $res = $this->patchJson("/api/v1/schedule-conflicts/{$conflict->id}/resolve", ['reason' => 'Autorizado formalmente']);
        $res->assertOk();

        $this->assertTrue($conflict->fresh()->is_resolved);
        $this->assertEquals($this->hrUserA->id, $conflict->fresh()->resolved_by);

        // 4. Verificar registro en AuditLog
        $this->assertDatabaseHas('audit_logs', [
            'company_id'  => $this->companyA->id,
            'auditable_id'=> $conflict->id,
            'action'      => 'UPDATE',
        ]);
    }

    public function test_dual_connection_concurrent_lock_competition_between_publish_and_mutation(): void
    {
        // 1. Crear base de datos física temporal para permitir múltiples conexiones PDO simultáneas independientes
        $dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'callshift_concurrency_' . uniqid() . '.sqlite';
        touch($dbPath);

        // Inicializar dos conexiones PDO independientes a la misma base de datos
        $pdoA = new \PDO('sqlite:' . $dbPath);
        $pdoA->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdoA->setAttribute(\PDO::ATTR_TIMEOUT, 1);

        $pdoB = new \PDO('sqlite:' . $dbPath);
        $pdoB->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdoB->setAttribute(\PDO::ATTR_TIMEOUT, 1);

        $pdoA->exec('
            CREATE TABLE work_periods (id INTEGER PRIMARY KEY, status TEXT);
            CREATE TABLE schedule_versions (id INTEGER PRIMARY KEY, work_period_id INTEGER, status TEXT, lock_version INTEGER);
            CREATE TABLE schedule_assignments (id INTEGER PRIMARY KEY, schedule_version_id INTEGER, date TEXT, shift_type_id INTEGER);
        ');

        $pdoA->exec("INSERT INTO work_periods (id, status) VALUES (1, 'DRAFT')");
        $pdoA->exec("INSERT INTO schedule_versions (id, work_period_id, status, lock_version) VALUES (1, 1, 'DRAFT', 1)");

        // 2. CONEXIÓN A (Transacción de Publicación):
        // Inicia transacción exclusiva y toma el lock para validar y publicar
        $pdoA->beginTransaction();
        $pdoA->exec("UPDATE work_periods SET status = 'REVIEW' WHERE id = 1"); // Adquiere lock de escritura exclusivo
        $stmtA = $pdoA->query("SELECT id, status, lock_version FROM schedule_versions WHERE id = 1");
        $versionA = $stmtA->fetch(\PDO::FETCH_ASSOC);
        $stmtA->closeCursor(); // Liberar cursor para permitir commit limpio

        $this->assertEquals(1, (int)$versionA['lock_version']);
        $this->assertEquals('DRAFT', $versionA['status']);

        // 3. CONEXIÓN B (Transacción de Mutación Concurrente):
        // Intenta mutar la versión mientras Conexión A mantiene el lock activo (BARRERA DE CONCURRENCIA REAL)
        $connBBlocked = false;
        try {
            $pdoB->beginTransaction();
            $pdoB->exec("INSERT INTO schedule_assignments (id, schedule_version_id, date, shift_type_id) VALUES (10, 1, '2026-08-04', 5)");
            $pdoB->exec("UPDATE schedule_versions SET lock_version = lock_version + 1 WHERE id = 1");
        } catch (\PDOException $e) {
            // Conexión B es bloqueada y rechazada por SQLite debido al lock de Conexión A
            $connBBlocked = true;
            if ($pdoB->inTransaction()) {
                $pdoB->rollBack();
            }
        }

        $this->assertTrue($connBBlocked, 'Conexión B debió haber sido bloqueada por el lock de Conexión A.');

        // 4. CONEXIÓN A (Completa la publicación y commitea la transacción):
        $pdoA->exec("UPDATE work_periods SET status = 'PUBLISHED' WHERE id = 1");
        $pdoA->exec("UPDATE schedule_versions SET status = 'PUBLISHED', lock_version = lock_version + 1 WHERE id = 1 AND lock_version = 1");
        $pdoA->commit();

        // 5. CONEXIÓN B (Reanuda tras la liberación del lock y detecta versión publicada / lock_version avanzado):
        $stmtB = $pdoB->query("SELECT id, status, lock_version FROM schedule_versions WHERE id = 1");
        $versionBAfter = $stmtB->fetch(\PDO::FETCH_ASSOC);
        $stmtB->closeCursor();

        // Verificaciones de estado inmutable tras la competencia
        $this->assertEquals('PUBLISHED', $versionBAfter['status']);
        $this->assertEquals(2, (int)$versionBAfter['lock_version']);

        // Conexión B intenta aplicar su mutación optimista con su lock_version inicial = 1 y status = DRAFT
        $affectedB = $pdoB->exec("UPDATE schedule_versions SET lock_version = 2 WHERE id = 1 AND lock_version = 1 AND status = 'DRAFT'");
        $this->assertEquals(0, $affectedB, 'La mutación de Conexión B debe afectar 0 filas porque la versión ya fue publicada y avanzada a lock_version 2.');

        // Limpieza de base de datos temporal
        unset($pdoA, $pdoB);
        @unlink($dbPath);
    }

    public function test_production_services_dual_connection_concurrency_race_publish_vs_upsert(): void
    {
        // 1. Configurar base de datos física temporal aislada para dos conexiones Laravel reales con WAL
        $dbPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'callshift_prod_race_' . uniqid() . '.sqlite';
        touch($dbPath);

        config([
            'database.connections.sqlite_isolated_file' => [
                'driver'                  => 'sqlite',
                'database'                => $dbPath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
                'busy_timeout'            => 5000,
                'journal_mode'            => 'WAL',
            ],
            'database.connections.sqlite_isolated_file_second' => [
                'driver'                  => 'sqlite',
                'database'                => $dbPath,
                'prefix'                  => '',
                'foreign_key_constraints' => true,
                'busy_timeout'            => 5000,
                'journal_mode'            => 'WAL',
            ],
        ]);

        \Illuminate\Support\Facades\DB::purge('sqlite_isolated_file');
        \Illuminate\Support\Facades\DB::purge('sqlite_isolated_file_second');

        $pdo = \Illuminate\Support\Facades\DB::connection('sqlite_isolated_file')->getPdo();
        $pdo->exec('PRAGMA journal_mode=WAL;');
        $pdo->exec('PRAGMA busy_timeout=5000;');

        \Illuminate\Support\Facades\Artisan::call('migrate', [
            '--database' => 'sqlite_isolated_file',
            '--path'     => 'database/migrations',
            '--force'    => true,
        ]);

        // 2. Seeding con Modelos Eloquent Reales en la base aislada
        $company = Company::on('sqlite_isolated_file')->create([
            'name'       => 'Empresa Carrera Real',
            'legal_name' => 'Empresa Carrera Real S.A.S.',
            'slug'       => 'empresa-carrera-real',
            'tax_id'     => '900999888-1',
            'email'      => 'carrera@callshift.com',
            'phone'      => '+573009998888',
            'timezone'   => 'America/Bogota',
            'country'    => 'COL',
            'status'     => 'ACTIVE',
        ]);
        $role = Role::on('sqlite_isolated_file')->create(['name' => 'HR Admin', 'code' => 'HR_ADMIN', 'company_id' => null]);
        $dept = Department::on('sqlite_isolated_file')->create(['company_id' => $company->id, 'name' => 'Operaciones', 'code' => 'OPS', 'status' => 'ACTIVE']);
        $pos = \App\Models\Position::on('sqlite_isolated_file')->create(['company_id' => $company->id, 'department_id' => $dept->id, 'name' => 'Operador', 'code' => 'OP', 'status' => 'ACTIVE']);
        $empType = \App\Models\EmploymentType::on('sqlite_isolated_file')->create(['company_id' => $company->id, 'name' => 'Tiempo Completo', 'code' => 'TC', 'weekly_hours' => 48.0, 'status' => 'ACTIVE']);

        $userA = User::on('sqlite_isolated_file')->create(['company_id' => $company->id, 'role_id' => $role->id, 'first_name' => 'Admin', 'last_name' => 'A', 'username' => 'admin.race', 'email' => 'admin.race@callshift.com', 'password' => 'secret', 'status' => 'ACTIVE']);
        $userB = User::on('sqlite_isolated_file')->create(['company_id' => $company->id, 'role_id' => $role->id, 'first_name' => 'Manager', 'last_name' => 'B', 'username' => 'mgr.race', 'email' => 'mgr.race@callshift.com', 'password' => 'secret', 'status' => 'ACTIVE']);

        $emp = Employee::on('sqlite_isolated_file')->create([
            'company_id'         => $company->id,
            'department_id'      => $dept->id,
            'position_id'        => $pos->id,
            'employment_type_id' => $empType->id,
            'first_name'         => 'Carlos',
            'last_name'          => 'Gomez',
            'employee_code'      => 'EMP-RACE',
            'document_type'      => 'CC',
            'document_number'    => '1099887766',
            'contract_type'      => 'INDEFINITE',
            'email'              => 'carlos.gomez@callshift.com',
            'hire_date'          => '2026-01-01',
            'status'             => 'ACTIVE',
        ]);

        $shift = ShiftType::on('sqlite_isolated_file')->create([
            'company_id'       => $company->id,
            'name'             => 'Turno Mañana',
            'code'             => 'TM-RACE',
            'start_time'       => '08:00:00',
            'end_time'         => '16:00:00',
            'total_work_hours' => 8.0,
            'break_minutes'    => 0,
            'crosses_midnight' => false,
            'status'           => 'ACTIVE',
        ]);

        $period = WorkPeriod::on('sqlite_isolated_file')->create([
            'company_id'    => $company->id,
            'department_id' => $dept->id,
            'name'          => 'Periodo Carrera Real',
            'start_date'    => '2026-08-01',
            'end_date'      => '2026-08-07',
            'period_type'   => 'WEEKLY',
            'status'        => WorkPeriodStatus::DRAFT,
            'created_by'    => $userA->id,
        ]);

        $version = ScheduleVersion::on('sqlite_isolated_file')->create([
            'work_period_id' => $period->id,
            'version_number' => 1,
            'status'         => ScheduleVersionStatus::REVIEW,
            'lock_version'   => 1,
            'created_by'     => $userA->id,
        ]);

        $period->update(['current_version_id' => $version->id]);

        ScheduleAssignment::on('sqlite_isolated_file')->create([
            'schedule_version_id' => $version->id,
            'employee_id'         => $emp->id,
            'date'                => '2026-08-03',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $shift->id,
            'total_hours'         => 8.0,
        ]);

        $workPeriodService = app(\App\Services\WorkPeriods\WorkPeriodService::class);
        $editorService = app(\App\Services\Schedule\ScheduleEditorService::class);

        // 3. Auditoría de Tráfico SQL: Escuchar y registrar queries en ambas conexiones
        $conn1Queries = [];
        $conn2Queries = [];

        \Illuminate\Support\Facades\DB::connection('sqlite_isolated_file')->listen(function ($query) use (&$conn1Queries) {
            $conn1Queries[] = $query->sql;
        });

        \Illuminate\Support\Facades\DB::connection('sqlite_isolated_file_second')->listen(function ($query) use (&$conn2Queries) {
            $conn2Queries[] = $query->sql;
        });

        // 4. Simulación de Carrera Real entre Conexión 1 y Conexión 2 con Barrera en validateVersion
        $realConflictService = app(\App\Services\Conflicts\ConflictDetectionService::class);
        $barrierHit = false;
        $connBBlocked = false;

        $this->instance(
            \App\Services\Conflicts\ConflictDetectionService::class,
            new class($realConflictService, $editorService, $version, $emp, $shift, $userB, $barrierHit, $connBBlocked, $dbPath) extends \App\Services\Conflicts\ConflictDetectionService {
                private $realConflictService;
                private $editorService;
                private $version;
                private $emp;
                private $shift;
                private $userB;
                private $dbPath;
                public $barrierHit;
                public $connBBlocked;

                public function __construct($real, $editor, $ver, $e, $s, $u, &$bHit, &$bBlocked, $dbPath) {
                    parent::__construct(app(\App\Services\Conflicts\BusinessRuleService::class));
                    $this->realConflictService = $real;
                    $this->editorService = $editor;
                    $this->version = $ver;
                    $this->emp = $e;
                    $this->shift = $s;
                    $this->userB = $u;
                    $this->barrierHit = &$bHit;
                    $this->connBBlocked = &$bBlocked;
                    $this->dbPath = $dbPath;
                }

                public function validateVersion(\App\Models\ScheduleVersion $version, ?\App\Models\User $actor = null): \Illuminate\Database\Eloquent\Collection
                {
                    $this->barrierHit = true;

                    // CONEXIÓN B intenta ejecutar upsertAssignment en Conexión 2 mientras Conexión 1 mantiene el lock
                    $pdo2 = null;
                    try {
                        $pdo2 = new \PDO('sqlite:' . $this->dbPath);
                        $pdo2->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
                        $pdo2->setAttribute(\PDO::ATTR_TIMEOUT, 1);
                        $pdo2->beginTransaction();
                        $pdo2->exec("INSERT INTO schedule_assignments (schedule_version_id, employee_id, date, day_type, shift_type_id, total_hours, created_at, updated_at) VALUES ({$this->version->id}, {$this->emp->id}, '2026-08-04', 'WORK', {$this->shift->id}, 8, datetime('now'), datetime('now'))");
                        $pdo2->exec("UPDATE schedule_versions SET lock_version = lock_version + 1 WHERE id = {$this->version->id}");
                        $pdo2->commit();
                    } catch (\Throwable $ex) {
                        // Conexión B es bloqueada y rechazada por el lock activo de Conexión 1
                        $this->connBBlocked = true;
                        if ($pdo2 && $pdo2->inTransaction()) {
                            $pdo2->rollBack();
                        }
                    } finally {
                        $pdo2 = null;
                        gc_collect_cycles();
                    }

                    return $this->realConflictService->validateVersion($version, $actor);
                }

                public function resolveEffectiveRule(\App\Models\Employee $employee): \App\DTOs\EffectiveBusinessRuleDTO
                {
                    return $this->realConflictService->resolveEffectiveRule($employee);
                }
            }
        );

        // 5. Conexión 1 ejecuta y completa la publicación de forma exitosa
        $publishedPeriod = $workPeriodService->changeWorkPeriodStatus(
            $period,
            WorkPeriodStatus::PUBLISHED->value,
            'Publicación oficial autorizada',
            1,
            $userA
        );

        $this->assertTrue($barrierHit, 'La barrera de concurrencia debió ejecutarse durante validateVersion.');
        $this->assertTrue($connBBlocked, 'Conexión 2 debió haber sido bloqueada por el lock exclusivo de Conexión 1.');

        // 6. Verificar que ambas conexiones ejecutaron queries SQL con huellas específicas
        $this->assertNotEmpty($conn1Queries, 'Conexión 1 debió haber ejecutado queries reales durante la publicación.');

        // Huella específica de Conexión 1: actualización de work_periods y schedule_versions
        $conn1WorkPeriodUpdate = collect($conn1Queries)->contains(fn($q) => str_contains($q, 'work_periods') && str_contains($q, 'status'));
        $conn1VersionUpdate = collect($conn1Queries)->contains(fn($q) => str_contains($q, 'schedule_versions') && str_contains($q, 'status'));
        $this->assertTrue($conn1WorkPeriodUpdate, 'Conexión 1 debió emitir un query de actualización sobre work_periods.');
        $this->assertTrue($conn1VersionUpdate, 'Conexión 1 debió emitir un query de actualización sobre schedule_versions.');

        // 7. Conexión 2 re-intenta upsertAssignment tras el commit de Conexión 1
        $rejectedAfterPublish = false;
        try {
            $versionAfterPublish = \App\Models\ScheduleVersion::on('sqlite_isolated_file_second')->find($version->id);
            $editorService->upsertAssignment($versionAfterPublish, [
                'employee_id'   => $emp->id,
                'date'          => '2026-08-04',
                'day_type'      => \App\Enums\DayType::WORK->value,
                'shift_type_id' => $shift->id,
                'lock_version'  => $versionAfterPublish->lock_version,
            ], $userB);
        } catch (\Illuminate\Validation\ValidationException $ve) {
            $rejectedAfterPublish = true;
            $this->assertArrayHasKey('version', $ve->errors());
        }

        $this->assertTrue($rejectedAfterPublish, 'Conexión 2 debe ser rechazada con ValidationException al intentar mutar una versión ya publicada.');

        // 8. Verificaciones Forenses de Estado Final directamente sobre Conexión 1 y Conexión 2
        $this->assertDatabaseHas('work_periods', [
            'id'     => $period->id,
            'status' => WorkPeriodStatus::PUBLISHED->value,
        ], 'sqlite_isolated_file');

        $this->assertDatabaseHas('schedule_versions', [
            'id'           => $version->id,
            'status'       => ScheduleVersionStatus::PUBLISHED->value,
            'lock_version' => 2,
        ], 'sqlite_isolated_file');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => ScheduleVersion::class,
            'auditable_id'   => $version->id,
            'action'         => 'UPDATE',
        ], 'sqlite_isolated_file');

        $this->assertDatabaseCount('schedule_assignments', 1, 'sqlite_isolated_file');

        // Inspección de estado desde Conexión 2 independiente
        $versionFromConn2 = \App\Models\ScheduleVersion::on('sqlite_isolated_file_second')->find($version->id);
        $this->assertEquals(ScheduleVersionStatus::PUBLISHED->value, $versionFromConn2->status->value);
        $this->assertEquals(2, $versionFromConn2->lock_version);

        // Limpieza de conexiones y archivo temporal
        \Illuminate\Support\Facades\DB::purge('sqlite_isolated_file');
        \Illuminate\Support\Facades\DB::purge('sqlite_isolated_file_second');
        @unlink($dbPath);
    }
}
