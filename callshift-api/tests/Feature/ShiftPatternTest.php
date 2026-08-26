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
use App\Models\ShiftPattern;
use App\Models\ShiftPatternEntry;
use App\Models\ShiftTemplate;
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

class ShiftPatternTest extends TestCase
{
    use RefreshDatabase;

    protected Company $companyA;
    protected Company $companyB;
    protected User $hrUserA;
    protected User $viewerUserA;
    protected User $hrUserB;
    protected Department $deptA;
    protected Department $deptA2;
    protected Department $deptB;
    protected Position $posA;
    protected Position $posB;
    protected EmploymentType $empTypeA;
    protected Employee $employeeA1;
    protected Employee $employeeA2;
    protected Employee $employeeADept2;
    protected Employee $employeeB;
    protected ShiftType $shiftDayA;
    protected ShiftType $shiftNightA;
    protected ShiftType $shift24hA;
    protected ShiftType $shiftInactiveA;
    protected ShiftType $shiftDayB;
    protected WorkPeriod $workPeriodA;
    protected ScheduleVersion $versionA;
    protected WorkPeriod $workPeriodB;
    protected ScheduleVersion $versionB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

        // Empresas
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

        $hrRole     = Role::where('code', RoleCode::HR_ADMIN->value)->first();
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
            'name'       => 'Tecnología',
            'code'       => 'TECH-01',
            'status'     => 'ACTIVE',
        ]);

        $this->posA = Position::create([
            'company_id'    => $this->companyA->id,
            'department_id' => $this->deptA->id,
            'name'          => 'Agente de Soporte',
            'code'          => 'AGT-SOP',
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
            'employee_code'      => 'EMP-A01',
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
            'employee_code'      => 'EMP-A02',
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
            'employee_code'      => 'EMP-A03',
            'first_name'         => 'Tech',
            'last_name'          => 'Lead',
            'document_number'    => '30303030',
            'email'              => 'tech@companya.com',
            'hire_date'          => '2025-01-01',
            'status'             => 'ACTIVE',
        ]);

        // Estructura y Empleado B
        $this->deptB = Department::create([
            'company_id' => $this->companyB->id,
            'name'       => 'Seguridad Privada',
            'code'       => 'SEG-01',
            'status'     => 'ACTIVE',
        ]);

        $this->posB = Position::create([
            'company_id'    => $this->companyB->id,
            'department_id' => $this->deptB->id,
            'name'          => 'Oficial de Seguridad',
            'code'          => 'OFC-01',
            'status'        => 'ACTIVE',
        ]);

        $empTypeB = EmploymentType::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Turno Continuo',
            'code'              => 'TC-48',
            'weekly_hours_base' => 48.0,
            'status'            => 'ACTIVE',
        ]);

        $this->employeeB = Employee::create([
            'company_id'         => $this->companyB->id,
            'department_id'      => $this->deptB->id,
            'position_id'        => $this->posB->id,
            'employment_type_id' => $empTypeB->id,
            'employee_code'      => 'EMP-B01',
            'first_name'         => 'Roberto',
            'last_name'          => 'Silva',
            'document_number'    => '40404040',
            'email'              => 'roberto@companyb.com',
            'hire_date'          => '2025-01-01',
            'status'             => 'ACTIVE',
        ]);

        // Turnos A
        $this->shiftDayA = ShiftType::create([
            'company_id'             => $this->companyA->id,
            'name'                   => 'Mañana (08:00 - 17:00)',
            'code'                   => 'TM-08',
            'color_hex'              => '#3B82F6',
            'start_time'             => '08:00:00',
            'end_time'               => '17:00:00',
            'break_duration_minutes' => 60,
            'total_work_hours'       => 8.0,
            'crosses_midnight'       => false,
            'status'                 => 'ACTIVE',
        ]);

        $this->shiftNightA = ShiftType::create([
            'company_id'             => $this->companyA->id,
            'name'                   => 'Noche (22:00 - 06:00)',
            'code'                   => 'TN-22',
            'color_hex'              => '#8B5CF6',
            'start_time'             => '22:00:00',
            'end_time'               => '06:00:00',
            'break_duration_minutes' => 0,
            'total_work_hours'       => 8.0,
            'crosses_midnight'       => true,
            'status'                 => 'ACTIVE',
        ]);

        $this->shift24hA = ShiftType::create([
            'company_id'             => $this->companyA->id,
            'name'                   => 'Guardia 24h',
            'code'                   => 'T24-08',
            'color_hex'              => '#F59E0B',
            'start_time'             => '08:00:00',
            'end_time'               => '08:00:00',
            'break_duration_minutes' => 120,
            'total_work_hours'       => 22.0,
            'crosses_midnight'       => true,
            'status'                 => 'ACTIVE',
        ]);

        $this->shiftInactiveA = ShiftType::create([
            'company_id'             => $this->companyA->id,
            'name'                   => 'Turno Antiguo Descontinuado',
            'code'                   => 'T-OLD',
            'color_hex'              => '#9CA3AF',
            'start_time'             => '09:00:00',
            'end_time'               => '18:00:00',
            'break_duration_minutes' => 60,
            'total_work_hours'       => 8.0,
            'crosses_midnight'       => false,
            'status'                 => 'INACTIVE',
        ]);

        // Turno B
        $this->shiftDayB = ShiftType::create([
            'company_id'             => $this->companyB->id,
            'name'                   => 'Turno Empresa B',
            'code'                   => 'TB-01',
            'color_hex'              => '#10B981',
            'start_time'             => '07:00:00',
            'end_time'               => '19:00:00',
            'break_duration_minutes' => 60,
            'total_work_hours'       => 11.0,
            'crosses_midnight'       => false,
            'status'                 => 'ACTIVE',
        ]);

        // Periodo y Versión A (Semana: 2026-08-24 al 2026-08-30)
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
            'department_id' => $this->deptB->id,
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

    // Helper: Crea un patrón 5x2 en Empresa A
    protected function create5x2Pattern(): ShiftPattern
    {
        $pattern = ShiftPattern::create([
            'company_id'        => $this->companyA->id,
            'department_id'     => $this->deptA->id,
            'name'              => 'Patrón Estándar 5x2',
            'code'              => 'PAT-5X2',
            'cycle_length_days' => 7,
            'description'       => 'Lunes a Viernes de 08:00 a 17:00, Sábado y Domingo Descanso',
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserA->id,
        ]);

        for ($d = 1; $d <= 5; $d++) {
            ShiftPatternEntry::create([
                'shift_pattern_id' => $pattern->id,
                'day_number'       => $d,
                'day_type'         => DayType::WORK,
                'shift_type_id'    => $this->shiftDayA->id,
            ]);
        }
        for ($d = 6; $d <= 7; $d++) {
            ShiftPatternEntry::create([
                'shift_pattern_id' => $pattern->id,
                'day_number'       => $d,
                'day_type'         => DayType::REST,
                'shift_type_id'    => null,
            ]);
        }

        return $pattern;
    }

    // TEST 1: Creación de patrón 5x2 válido vía API
    public function test_authorized_user_can_create_5x2_shift_pattern(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Turno Administrativo 5x2',
            'code'              => 'ADM-5X2',
            'cycle_length_days' => 7,
            'description'       => '5 días laborables y 2 días de descanso',
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 2, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 3, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 4, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 5, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 6, 'day_type' => 'REST', 'shift_type_id' => null],
                ['day_number' => 7, 'day_type' => 'REST', 'shift_type_id' => null],
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'ADM-5X2')
            ->assertJsonPath('data.cycle_length_days', 7)
            ->assertJsonCount(7, 'data.entries');

        $this->assertDatabaseHas('shift_patterns', [
            'company_id' => $this->companyA->id,
            'code'       => 'ADM-5X2',
        ]);
    }

    // TEST 2: Creación de patrón rotativo 4x2 (Mañana / Noche / Descanso)
    public function test_authorized_user_can_create_rotational_4x2_pattern(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Rotativo 4x2',
            'code'              => 'ROT-4X2',
            'cycle_length_days' => 6,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 2, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 3, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftNightA->id],
                ['day_number' => 4, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftNightA->id],
                ['day_number' => 5, 'day_type' => 'REST', 'shift_type_id' => null],
                ['day_number' => 6, 'day_type' => 'REST', 'shift_type_id' => null],
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);

        $response->assertCreated()
            ->assertJsonPath('data.cycle_length_days', 6)
            ->assertJsonCount(6, 'data.entries');
    }

    // TEST 3: Rechazo cuando la cantidad de entradas no coincide con cycle_length_days
    public function test_rejects_pattern_when_entries_count_mismatches_cycle_length(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón Incompleto',
            'code'              => 'INC-01',
            'cycle_length_days' => 7,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 2, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
            ], // Solo 2 entradas para un ciclo de 7
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['entries']);
    }

    // TEST 4: Rechazo cuando hay días de ciclo duplicados
    public function test_rejects_pattern_with_duplicate_day_numbers(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón Duplicado',
            'code'              => 'DUP-01',
            'cycle_length_days' => 2,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 1, 'day_type' => 'REST', 'shift_type_id' => null], // Duplicado día 1
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertStatus(422);
    }

    // TEST 5: Rechazo cuando entrada WORK no tiene shift_type_id
    public function test_rejects_work_entry_without_shift_type(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón Sin Turno',
            'code'              => 'NOTYPE-01',
            'cycle_length_days' => 1,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => null],
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertStatus(422);
    }

    // TEST 6: Rechazo cuando shift_type_id está inactivo
    public function test_rejects_inactive_shift_type_in_pattern(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón Con Turno Inactivo',
            'code'              => 'INACT-01',
            'cycle_length_days' => 1,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftInactiveA->id],
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['entries.0.shift_type_id']);
    }

    // TEST 7: Cross-tenant rechazo al usar ShiftType de otra empresa
    public function test_cross_tenant_shift_type_in_pattern_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón Con Turno Ajeno',
            'code'              => 'CROSS-01',
            'cycle_length_days' => 1,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayB->id], // Empresa B
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['entries.0.shift_type_id']);
    }

    // TEST 8: Cross-tenant lectura de patrón ajeno denegada
    public function test_cross_tenant_view_pattern_is_blocked(): void
    {
        $patternB = ShiftPattern::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Patrón Empresa B',
            'code'              => 'PAT-B',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->getJson("/api/v1/shift-patterns/{$patternB->id}");
        $response->assertNotFound();
    }

    // TEST 9: Cross-tenant modificación de patrón ajeno denegada
    public function test_cross_tenant_update_pattern_is_blocked(): void
    {
        $patternB = ShiftPattern::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Patrón Empresa B',
            'code'              => 'PAT-B',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->putJson("/api/v1/shift-patterns/{$patternB->id}", ['name' => 'Hack']);
        $response->assertNotFound();
    }

    // TEST 10: Cross-tenant eliminación de patrón ajeno denegada
    public function test_cross_tenant_delete_pattern_is_blocked(): void
    {
        $patternB = ShiftPattern::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Patrón Empresa B',
            'code'              => 'PAT-B',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->deleteJson("/api/v1/shift-patterns/{$patternB->id}");
        $response->assertNotFound();
    }

    // TEST 11: company_id en payload es ignorado y derivado de $actor
    public function test_company_id_in_payload_is_ignored_and_derived_from_actor(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'company_id'        => $this->companyB->id, // Intento de spoofing
            'name'              => 'Patrón Spoofing',
            'code'              => 'SPF-01',
            'cycle_length_days' => 1,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'REST', 'shift_type_id' => null],
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertCreated();

        $this->assertDatabaseHas('shift_patterns', [
            'company_id' => $this->companyA->id, // Debe ser A
            'code'       => 'SPF-01',
        ]);
    }

    // TEST 12: Mismo código de patrón en diferentes empresas es permitido
    public function test_same_pattern_code_in_different_companies_is_permitted(): void
    {
        ShiftPattern::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Patrón B',
            'code'              => 'PAT-5X2',
            'cycle_length_days' => 7,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón A',
            'code'              => 'PAT-5X2',
            'cycle_length_days' => 1,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'REST', 'shift_type_id' => null],
            ],
        ];

        $response = $this->postJson('/api/v1/shift-patterns', $payload);
        $response->assertCreated();
    }

    // TEST 13: Actualización autorizada regenera entradas de patrón
    public function test_authorized_update_regenerates_pattern_entries(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'name'              => 'Patrón 6x1 Actualizado',
            'cycle_length_days' => 7,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 2, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 3, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 4, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 5, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 6, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 7, 'day_type' => 'REST', 'shift_type_id' => null],
            ],
        ];

        $response = $this->putJson("/api/v1/shift-patterns/{$pattern->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.name', 'Patrón 6x1 Actualizado');

        $this->assertEquals(6, $pattern->entries()->where('day_type', 'WORK')->count());
        $this->assertEquals(1, $pattern->entries()->where('day_type', 'REST')->count());
    }

    // TEST 14: Eliminación lógica autorizada
    public function test_authorized_deletion_soft_deletes_pattern(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $response = $this->deleteJson("/api/v1/shift-patterns/{$pattern->id}");
        $response->assertOk();

        $this->assertSoftDeleted('shift_patterns', ['id' => $pattern->id]);
    }

    // TEST 15: CRUD de Plantillas (ShiftTemplate)
    public function test_shift_template_crud_operations(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        // 1. Crear Plantilla
        $createRes = $this->postJson('/api/v1/shift-templates', [
            'name'             => 'Plantilla Call Center Q3',
            'code'             => 'TPL-CC-Q3',
            'department_id'    => $this->deptA->id,
            'shift_pattern_id' => $pattern->id,
            'description'      => 'Plantilla base de operaciones',
        ]);
        $createRes->assertCreated()
            ->assertJsonPath('data.code', 'TPL-CC-Q3');

        $tplId = $createRes->json('data.id');

        // 2. Listar
        $this->getJson('/api/v1/shift-templates')->assertOk()->assertJsonCount(1, 'data');

        // 3. Modificar
        $this->putJson("/api/v1/shift-templates/{$tplId}", ['name' => 'Plantilla CC Modificada'])->assertOk()
            ->assertJsonPath('data.name', 'Plantilla CC Modificada');

        // 4. Eliminar
        $this->deleteJson("/api/v1/shift-templates/{$tplId}")->assertOk();
        $this->assertSoftDeleted('shift_templates', ['id' => $tplId]);
    }

    // TEST 16: Usuario VIEWER denegado para mutaciones (RBAC)
    public function test_viewer_role_cannot_create_or_modify_patterns(): void
    {
        Sanctum::actingAs($this->viewerUserA);

        $this->getJson('/api/v1/shift-patterns')->assertOk(); // Puede ver

        $this->postJson('/api/v1/shift-patterns', [
            'name'              => 'Intento Viewer',
            'code'              => 'VIEW-01',
            'cycle_length_days' => 1,
            'entries'           => [['day_number' => 1, 'day_type' => 'REST', 'shift_type_id' => null]],
        ])->assertForbidden();
    }

    // TEST 17: Previsualización simula asignaciones de patrón 5x2 sin persistir (Dry-Run)
    public function test_preview_simulates_pattern_application_without_persisting(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'        => $pattern->id,
            'employee_ids'      => [$this->employeeA1->id, $this->employeeA2->id],
            'start_offset_day'  => 1, // Lunes inicia con Día 1
            'lock_version'      => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern/preview", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.employees_count', 2)
            ->assertJsonPath('data.summary.total_days_in_period', 7) // Semana completa
            ->assertJsonPath('data.summary.total_assignments', 14)  // 2 emp * 7 días
            ->assertJsonPath('data.summary.total_work_days', 10)    // 5 días * 2 emp
            ->assertJsonPath('data.summary.total_rest_days', 4);    // 2 días * 2 emp

        // Confirmar que NADA fue guardado en la base de datos
        $this->assertEquals(0, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
    }

    // TEST 18: Previsualización rechaza empleado de otra empresa
    public function test_preview_rejects_cross_tenant_employee(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeB->id], // Empresa B
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern/preview", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids.0']);
    }

    // TEST 19: Previsualización rechaza empleado de departamento incompatible
    public function test_preview_rejects_employee_with_department_mismatch(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeADept2->id], // Dept Tecnología vs Periodo Operaciones
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern/preview", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids']);
    }

    // TEST 20: Aplicación masiva 5x2 persiste asignaciones correctamente
    public function test_apply_pattern_persists_bulk_assignments_atomically(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id, $this->employeeA2->id],
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.lock_version', 2)
            ->assertJsonPath('data.persisted_count', 14);

        // Verificar incremento en base de datos
        $this->assertEquals(2, $this->versionA->fresh()->lock_version);
        $this->assertEquals(14, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());

        // Verificar asignación del primer día (Lunes 2026-08-24)
        $mondayAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();

        $this->assertEquals(DayType::WORK, $mondayAss->day_type);
        $this->assertEquals($this->shiftDayA->id, $mondayAss->shift_type_id);
        $this->assertEquals('2026-08-24 08:00:00', $mondayAss->starts_at->format('Y-m-d H:i:s'));

        // Verificar asignación del domingo (2026-08-30) -> REST
        $sundayAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-30')
            ->first();

        $this->assertEquals(DayType::REST, $sundayAss->day_type);
        $this->assertNull($sundayAss->shift_type_id);
    }

    // TEST 21: Turno nocturno con cruce de medianoche calcula ends_at en D+1
    public function test_pattern_with_night_shift_calculates_ends_at_plus_one_day(): void
    {
        $nightPattern = ShiftPattern::create([
            'company_id'        => $this->companyA->id,
            'name'              => 'Patrón Nocturno',
            'code'              => 'PAT-NIGHT',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserA->id,
        ]);
        ShiftPatternEntry::create([
            'shift_pattern_id' => $nightPattern->id,
            'day_number'       => 1,
            'day_type'         => DayType::WORK,
            'shift_type_id'    => $this->shiftNightA->id, // 22:00 -> 06:00
        ]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $nightPattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload)->assertOk();

        $ass = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();

        $this->assertEquals('2026-08-24 22:00:00', $ass->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-25 06:00:00', $ass->ends_at->format('Y-m-d H:i:s'));
    }

    // TEST 22: Turno 24 horas calcula timestamps y duración exacta
    public function test_pattern_with_24h_shift_calculates_correct_duration(): void
    {
        $p24 = ShiftPattern::create([
            'company_id'        => $this->companyA->id,
            'name'              => 'Patrón Guardia 24h',
            'code'              => 'PAT-24H',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserA->id,
        ]);
        ShiftPatternEntry::create([
            'shift_pattern_id' => $p24->id,
            'day_number'       => 1,
            'day_type'         => DayType::WORK,
            'shift_type_id'    => $this->shift24hA->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $p24->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload)->assertOk();

        $ass = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();

        $this->assertEquals('2026-08-24 08:00:00', $ass->starts_at->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-25 08:00:00', $ass->ends_at->format('Y-m-d H:i:s'));
        $this->assertEquals(22.0, (float) $ass->total_hours);
    }

    // TEST 23: Desfase inicial de ciclo (start_offset_day = 6 inicia con descanso en lunes)
    public function test_start_offset_day_shifts_cycle_sequence_correctly(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'       => $pattern->id,
            'employee_ids'     => [$this->employeeA1->id],
            'start_offset_day' => 6, // Día 6 es REST
            'lock_version'     => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload)->assertOk();

        // Lunes 24 (Día 6 del ciclo) -> REST
        $monAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();
        $this->assertEquals(DayType::REST, $monAss->day_type);

        // Martes 25 (Día 7 del ciclo) -> REST
        $tueAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-25')
            ->first();
        $this->assertEquals(DayType::REST, $tueAss->day_type);

        // Miércoles 26 (Día 1 del ciclo) -> WORK
        $wedAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-26')
            ->first();
        $this->assertEquals(DayType::WORK, $wedAss->day_type);
    }

    // TEST 24: Sobreescritura idempotente actualiza celdas existentes sin duplicados
    public function test_reapplying_pattern_overwrites_cleanly_without_duplicate_rows(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        // 1. Primera aplicación
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ])->assertOk();

        $this->assertEquals(7, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());

        // 2. Segunda aplicación (con lock_version = 2)
        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 2,
        ])->assertOk();

        // Debe seguir habiendo exactamente 7 registros
        $this->assertEquals(7, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
        $this->assertEquals(3, $this->versionA->fresh()->lock_version);
    }

    // TEST 25: Concurrencia optimista (lock_version obsoleto devuelve 409 Conflict y aborta)
    public function test_stale_lock_version_returns_409_conflict_and_aborts_application(): void
    {
        $pattern = $this->create5x2Pattern();
        $this->versionA->update(['lock_version' => 10]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 9, // Desactualizado
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);

        $response->assertStatus(409)
            ->assertJsonPath('current_lock_version', 10);

        $this->assertEquals(0, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
        $this->assertEquals(10, $this->versionA->fresh()->lock_version);
    }

    // TEST 26: Versión PUBLISHED bloquea aplicación de patrón
    public function test_published_version_blocks_pattern_application(): void
    {
        $pattern = $this->create5x2Pattern();
        $this->versionA->update(['status' => ScheduleVersionStatus::PUBLISHED]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);
        $response->assertForbidden();
    }

    // TEST 27: WorkPeriod CLOSED bloquea aplicación de patrón
    public function test_closed_work_period_blocks_pattern_application(): void
    {
        $pattern = $this->create5x2Pattern();
        $this->workPeriodA->update(['status' => WorkPeriodStatus::CLOSED]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);
        $response->assertForbidden();
    }

    // TEST 28: Registro de auditoría generado tras aplicación masiva
    public function test_pattern_application_generates_audit_logs(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload)->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrUserA->id,
            'auditable_type' => ScheduleVersion::class,
            'auditable_id'   => (string) $this->versionA->id,
            'action'         => 'UPDATE',
        ]);
    }

    // TEST 29: Cross-tenant rechazo al asociar patrón de otro tenant en plantilla
    public function test_cross_tenant_pattern_in_template_is_rejected(): void
    {
        $patternB = ShiftPattern::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Patrón B',
            'code'              => 'PAT-B-01',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserB->id,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $response = $this->postJson('/api/v1/shift-templates', [
            'name'             => 'Plantilla A con Patrón B',
            'code'             => 'TPL-CROSS',
            'shift_pattern_id' => $patternB->id,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shift_pattern_id']);
    }

    // TEST 30: Cross-tenant rechazo al asociar departamento ajeno a un patrón
    public function test_cross_tenant_department_in_pattern_is_rejected(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $response = $this->postJson('/api/v1/shift-patterns', [
            'name'              => 'Patrón A con Dept B',
            'code'              => 'PAT-CROSS-DEPT',
            'department_id'     => $this->deptB->id, // Dept de Empresa B
            'cycle_length_days' => 1,
            'entries'           => [['day_number' => 1, 'day_type' => 'REST', 'shift_type_id' => null]],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['department_id']);
    }

    // TEST 31: Fecha de aplicación antes del WorkPeriod es rechazada
    public function test_application_date_before_work_period_start_is_rejected(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'start_date'   => '2026-08-20', // Periodo inicia 2026-08-24
            'end_date'     => '2026-08-28',
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['start_date']);
    }

    // TEST 32: Fecha de aplicación después del WorkPeriod es rechazada
    public function test_application_date_after_work_period_end_is_rejected(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'start_date'   => '2026-08-25',
            'end_date'     => '2026-09-05', // Periodo termina 2026-08-30
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    // TEST 33: Lista vacía de empleados es rechazada
    public function test_empty_employee_ids_is_rejected(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [],
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids']);
    }

    // TEST 34: Patrón 6x1 calcula correctamente 6 días laborales y 1 descanso
    public function test_pattern_6x1_cycle_calculation_and_application(): void
    {
        $p6x1 = ShiftPattern::create([
            'company_id'        => $this->companyA->id,
            'name'              => 'Patrón 6x1',
            'code'              => 'PAT-6X1',
            'cycle_length_days' => 7,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserA->id,
        ]);
        for ($d = 1; $d <= 6; $d++) {
            ShiftPatternEntry::create([
                'shift_pattern_id' => $p6x1->id,
                'day_number'       => $d,
                'day_type'         => DayType::WORK,
                'shift_type_id'    => $this->shiftDayA->id,
            ]);
        }
        ShiftPatternEntry::create([
            'shift_pattern_id' => $p6x1->id,
            'day_number'       => 7,
            'day_type'         => DayType::REST,
            'shift_type_id'    => null,
        ]);

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $p6x1->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ];

        $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload)->assertOk();

        $workCount = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('day_type', DayType::WORK)
            ->count();

        $restCount = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('day_type', DayType::REST)
            ->count();

        $this->assertEquals(6, $workCount);
        $this->assertEquals(1, $restCount);
    }

    // TEST 35: Aplicación en rango parcial de fechas dentro del periodo
    public function test_partial_date_range_application_within_work_period(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'start_date'   => '2026-08-25', // Martes
            'end_date'     => '2026-08-27', // Jueves (3 días)
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);

        $response->assertOk()
            ->assertJsonPath('data.persisted_count', 3);

        $this->assertEquals(3, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
    }

    // TEST 36: ROLLBACK REAL — Fallo forzado a mitad de la persistencia cancela todas las escrituras
    public function test_atomic_rollback_on_intermediate_failure_during_apply(): void
    {
        $pattern = $this->create5x2Pattern();
        $initialLogsCount = AuditLog::count();

        Sanctum::actingAs($this->hrUserA);

        // Interceptar la creación del modelo ScheduleAssignment para forzar una excepción en el segundo empleado
        $createdCount = 0;
        ScheduleAssignment::creating(function ($assignment) use (&$createdCount) {
            $createdCount++;
            // Tras haber creado al menos 3 asignaciones del primer empleado, forzar fallo catastrófico
            if ($createdCount === 4) {
                throw new \RuntimeException('Error forzado simulado para verificar rollback transaccional.');
            }
        });

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id, $this->employeeA2->id],
            'lock_version' => 1,
        ];

        try {
            $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);
        } catch (\Throwable $e) {
            // Excepción capturada para comprobar la consistencia de la base de datos
        }

        // VERIFICACIONES OBLIGATORIAS DE AUDITORÍA FORENSE:
        // 1. Cero asignaciones persistidas en base de datos
        $this->assertEquals(0, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
        // 2. Ninguna asignación parcial del Empleado A1 persistió
        $this->assertDatabaseMissing('schedule_assignments', ['employee_id' => $this->employeeA1->id]);
        // 3. Ninguna asignación del Empleado A2 persistió
        $this->assertDatabaseMissing('schedule_assignments', ['employee_id' => $this->employeeA2->id]);
        // 4. lock_version permanece intacto (1)
        $this->assertEquals(1, $this->versionA->fresh()->lock_version);
        // 5. Cero logs de auditoría de éxito registrados tras el rollback
        $this->assertDatabaseMissing('audit_logs', [
            'description' => "Patrón '{$pattern->name}' aplicado masivamente",
        ]);
        $this->assertEquals($initialLogsCount, AuditLog::count());
    }

    // TEST 37: PREVIEW → CONCURRENT UPDATE → APPLY REJECTION (409 CONFLICT)
    public function test_preview_followed_by_concurrent_modification_causes_409_and_no_writes(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        // 1. Simulación exitosa obteniendo lock_version = 1
        $previewRes = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern/preview", [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ]);
        $previewRes->assertOk();
        $capturedLock = $previewRes->json('data.version.lock_version');
        $this->assertEquals(1, $capturedLock);

        // 2. Operación concurrente modifica la versión en paralelo
        $this->versionA->update(['lock_version' => 2]);
        $logsBeforeApply = AuditLog::count();

        // 3. Intento de Apply usando el lock_version desactualizado (1)
        $applyRes = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => $capturedLock, // 1 vs 2 actual
        ]);

        $applyRes->assertStatus(409)
            ->assertJsonPath('current_lock_version', 2);

        // 4. Verificaciones de aislamiento y no-modificación
        $this->assertEquals(0, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
        $this->assertEquals(2, $this->versionA->fresh()->lock_version);
        $this->assertDatabaseMissing('audit_logs', [
            'description' => "Patrón '{$pattern->name}' aplicado masivamente",
        ]);
        $this->assertEquals($logsBeforeApply, AuditLog::count());
    }

    // TEST 38: ATAQUES CROSS-TENANT DIRECTOS CONTRA APPLY
    public function test_direct_cross_tenant_apply_attacks_are_all_blocked(): void
    {
        $patternB = ShiftPattern::create([
            'company_id'        => $this->companyB->id,
            'name'              => 'Patrón Empresa B',
            'code'              => 'PAT-B-ATTACK',
            'cycle_length_days' => 1,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserB->id,
        ]);

        $patternA = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        // Ataque 1: Patrón de Tenant B sobre Versión de Tenant A
        $res1 = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", [
            'pattern_id'   => $patternB->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ]);
        $res1->assertStatus(422)
            ->assertJsonValidationErrors(['pattern_id']);

        // Ataque 2: Empleado de Tenant B sobre Versión de Tenant A
        $res2 = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", [
            'pattern_id'   => $patternA->id,
            'employee_ids' => [$this->employeeB->id],
            'lock_version' => 1,
        ]);
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['employee_ids.0']);

        // Ataque 3: Versión de Tenant B atacada por usuario de Tenant A
        $res3 = $this->postJson("/api/v1/schedule-versions/{$this->versionB->id}/apply-pattern", [
            'pattern_id'   => $patternA->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ]);
        $res3->assertForbidden();
    }

    // TEST 39: POLÍTICA EXPLICITA DE OVERRIDE_EXISTING = FALSE (PRESERVA EXISTENTES)
    public function test_override_existing_false_preserves_current_assignments_and_populates_gaps(): void
    {
        $pattern = $this->create5x2Pattern();

        // Crear una asignación previa para el lunes (2026-08-24) con turno noche TN-22
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNightA->id,
            'starts_at'           => '2026-08-24 22:00:00',
            'ends_at'             => '2026-08-25 06:00:00',
            'total_hours'         => 8.0,
            'notes'               => 'Turno previo preestablecido',
        ]);

        Sanctum::actingAs($this->hrUserA);

        // Aplicar con override_existing = false
        $payload = [
            'pattern_id'        => $pattern->id,
            'employee_ids'      => [$this->employeeA1->id],
            'override_existing' => false,
            'lock_version'      => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);

        $response->assertOk()
            ->assertJsonPath('data.persisted_count', 6); // Solo 6 días nuevos (Martes a Domingo)

        // Verificar que el Lunes 24 PRESERVÓ intacto el turno nocturno previo
        $mondayAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();

        $this->assertEquals($this->shiftNightA->id, $mondayAss->shift_type_id);
        $this->assertEquals('Turno previo preestablecido', $mondayAss->notes);

        // Verificar que el Martes 25 sí recibió el turno del patrón (TM-08)
        $tuesdayAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-25')
            ->first();

        $this->assertEquals($this->shiftDayA->id, $tuesdayAss->shift_type_id);
    }

    // TEST 40: POLÍTICA EXPLICITA DE OVERRIDE_EXISTING = TRUE (SOBREESCRIBE EN SU LUGAR SIN DUPLICADOS)
    public function test_override_existing_true_overwrites_in_place_without_duplicate_records(): void
    {
        $pattern = $this->create5x2Pattern();

        // Crear una asignación previa para el lunes con turno noche TN-22
        ScheduleAssignment::create([
            'schedule_version_id' => $this->versionA->id,
            'employee_id'         => $this->employeeA1->id,
            'date'                => '2026-08-24',
            'day_type'            => DayType::WORK,
            'shift_type_id'       => $this->shiftNightA->id,
            'starts_at'           => '2026-08-24 22:00:00',
            'ends_at'             => '2026-08-25 06:00:00',
            'total_hours'         => 8.0,
        ]);

        Sanctum::actingAs($this->hrUserA);

        // Aplicar con override_existing = true (default)
        $payload = [
            'pattern_id'        => $pattern->id,
            'employee_ids'      => [$this->employeeA1->id],
            'override_existing' => true,
            'lock_version'      => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);

        $response->assertOk()
            ->assertJsonPath('data.persisted_count', 7);

        // Verificar que el Lunes 24 fue REEMPLAZADO por el turno diurno del patrón (TM-08)
        $mondayAss = ScheduleAssignment::where('schedule_version_id', $this->versionA->id)
            ->where('employee_id', $this->employeeA1->id)
            ->where('date', '2026-08-24')
            ->first();

        $this->assertEquals($this->shiftDayA->id, $mondayAss->shift_type_id);
        $this->assertEquals(7, ScheduleAssignment::where('schedule_version_id', $this->versionA->id)->count());
    }

    // TEST 41: SINGLE ATOMIC INCREMENT DE LOCK_VERSION PARA MÚLTIPLES EMPLEADOS
    public function test_bulk_apply_for_multiple_employees_increments_lock_version_exactly_once(): void
    {
        $pattern = $this->create5x2Pattern();

        Sanctum::actingAs($this->hrUserA);

        $payload = [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id, $this->employeeA2->id],
            'lock_version' => 1,
        ];

        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", $payload);

        $response->assertOk()
            ->assertJsonPath('data.lock_version', 2); // De 1 a 2 exactamente (NO 1 + 2 * 7)

        $this->assertEquals(2, $this->versionA->fresh()->lock_version);
    }

    // TEST 42: ENTIDADES SOFT-DELETED NO PUEDEN USARSE EN APPLY NI ACCEDERSE DIRECTAMENTE
    public function test_soft_deleted_pattern_cannot_be_used_in_apply(): void
    {
        $pattern = $this->create5x2Pattern();
        $pattern->delete(); // Soft delete

        Sanctum::actingAs($this->hrUserA);

        // 1. Acceso directo por ID da 404
        $this->getJson("/api/v1/shift-patterns/{$pattern->id}")->assertNotFound();

        // 2. Intento de Apply con patrón eliminado es rechazado (404/422)
        $response = $this->postJson("/api/v1/schedule-versions/{$this->versionA->id}/apply-pattern", [
            'pattern_id'   => $pattern->id,
            'employee_ids' => [$this->employeeA1->id],
            'lock_version' => 1,
        ]);
        $response->assertNotFound();
    }

    // TEST 43: ENGINE DETERMINISTA PURO (Mapeos matemáticos de ciclo y límites)
    public function test_pattern_engine_service_deterministic_cycles_and_boundaries(): void
    {
        $engine = new \App\Services\Shifts\PatternEngineService();

        // Ciclo de 7 días (5x2)
        $this->assertEquals(1, $engine->calculateCycleDayNumber(0, 7, 1));
        $this->assertEquals(5, $engine->calculateCycleDayNumber(4, 7, 1));
        $this->assertEquals(7, $engine->calculateCycleDayNumber(6, 7, 1));
        $this->assertEquals(1, $engine->calculateCycleDayNumber(7, 7, 1)); // Vuelve a día 1
        $this->assertEquals(2, $engine->calculateCycleDayNumber(8, 7, 1));

        // Offset inicial 3
        $this->assertEquals(3, $engine->calculateCycleDayNumber(0, 7, 3));
        $this->assertEquals(7, $engine->calculateCycleDayNumber(4, 7, 3));
        $this->assertEquals(1, $engine->calculateCycleDayNumber(5, 7, 3));

        // Ciclo de 1 día
        $this->assertEquals(1, $engine->calculateCycleDayNumber(0, 1, 1));
        $this->assertEquals(1, $engine->calculateCycleDayNumber(50, 1, 1));

        // Ciclo de 6 días (4x2 rotativo)
        $this->assertEquals(6, $engine->calculateCycleDayNumber(5, 6, 1));
        $this->assertEquals(1, $engine->calculateCycleDayNumber(6, 6, 1));
    }

    // TEST 44: GARANTÍA FÍSICA EN BASE DE DATOS — UNIQUE(company_id, code) RECHAZA DUPLICADOS ACTIVOS A NIVEL DE MOTOR DB
    public function test_database_level_unique_constraint_enforces_physical_uniqueness_for_patterns(): void
    {
        // 1. Crear patrón válido inicial
        ShiftPattern::create([
            'company_id'        => $this->companyA->id,
            'name'              => 'Patrón Original',
            'code'              => 'UNIQUE-DB-01',
            'cycle_length_days' => 7,
            'status'            => 'ACTIVE',
            'created_by'        => $this->hrUserA->id,
        ]);

        // 2. Intentar inserción directa en base de datos bypaseando FormRequest
        $this->expectException(\Illuminate\Database\QueryException::class);

        \Illuminate\Support\Facades\DB::table('shift_patterns')->insert([
            'company_id'        => $this->companyA->id,
            'name'              => 'Patrón Duplicado Intencional',
            'code'              => 'UNIQUE-DB-01', // Mismo código
            'cycle_length_days' => 7,
            'status'            => 'ACTIVE',
            'created_at'        => now(),
            'updated_at'        => now(),
        ]);
    }

    // TEST 45: CONDICIÓN DE CARRERA CONCURRENTE — PERSISTENCIA SIMULTÁNEA DE MISMO CÓDIGO PERMITE EXACTAMENTE UNO
    public function test_concurrent_pattern_creation_race_condition_prevented_by_database_constraint(): void
    {
        Sanctum::actingAs($this->hrUserA);

        $payload1 = [
            'name'              => 'Patrón Concurrente 1',
            'code'              => 'RACE-PAT-99',
            'cycle_length_days' => 7,
            'entries'           => [
                ['day_number' => 1, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 2, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 3, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 4, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 5, 'day_type' => 'WORK', 'shift_type_id' => $this->shiftDayA->id],
                ['day_number' => 6, 'day_type' => 'REST'],
                ['day_number' => 7, 'day_type' => 'REST'],
            ],
        ];

        // Primera petición: exitosa (201 Created)
        $res1 = $this->postJson('/api/v1/shift-patterns', $payload1);
        $res1->assertCreated();

        // Segunda petición idéntica / simultánea con el mismo código: rechazada por validación y constraint (422)
        $res2 = $this->postJson('/api/v1/shift-patterns', $payload1);
        $res2->assertStatus(422)
            ->assertJsonValidationErrors(['code']);

        // Verificación de integridad física en base de datos: exactamente 1 registro existe
        $this->assertEquals(1, ShiftPattern::where('company_id', $this->companyA->id)->where('code', 'RACE-PAT-99')->count());
    }
}



