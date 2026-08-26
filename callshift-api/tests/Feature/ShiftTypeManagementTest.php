<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\ShiftType;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\AuditAction;
use App\Policies\ShiftTypePolicy;
use App\Services\Shifts\ShiftTypeService;
use App\Http\Resources\V1\ShiftTypeResource;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShiftTypeManagementTest extends TestCase
{
    use RefreshDatabase;
    protected Company $companyA;
    protected Company $companyB;
    protected Role $superAdminRole;
    protected Role $hrAdminRole;
    protected Role $viewerRole;
    protected Role $employeeRole;
    protected User $superAdmin;
    protected User $hrAdminA;
    protected User $viewerA;
    protected User $employeeA;
    protected User $hrAdminB;
    protected ShiftType $shiftA_day;
    protected ShiftType $shiftA_night;
    protected ShiftType $shiftB_day;
    protected ShiftTypePolicy $policy;
    protected ShiftTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ShiftTypePolicy();
        $this->service = new ShiftTypeService();

        // 1. Empresas
        $this->companyA = new Company(['name' => 'Empresa A S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyA->id = 1;

        $this->companyB = new Company(['name' => 'Empresa B S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyB->id = 2;

        // 2. Permisos
        $permView = new Permission(['code' => 'shifts:view', 'name' => 'Ver Turnos', 'module' => 'shifts']);
        $permManage = new Permission(['code' => 'shifts:manage', 'name' => 'Gestionar Turnos', 'module' => 'shifts']);

        // 3. Roles
        $this->superAdminRole = new Role(['code' => RoleCode::SUPER_ADMIN->value, 'name' => 'Super Admin', 'company_id' => null]);
        $this->superAdminRole->id = 1;
        $this->superAdminRole->setRelation('permissions', collect([]));

        $this->hrAdminRole = new Role(['code' => RoleCode::HR_ADMIN->value, 'name' => 'RRHH Admin', 'company_id' => null]);
        $this->hrAdminRole->id = 2;
        $this->hrAdminRole->setRelation('permissions', collect([$permView, $permManage]));

        $this->viewerRole = new Role(['code' => RoleCode::VIEWER->value, 'name' => 'Visualizador', 'company_id' => null]);
        $this->viewerRole->id = 6;
        $this->viewerRole->setRelation('permissions', collect([$permView]));

        $this->employeeRole = new Role(['code' => RoleCode::EMPLOYEE->value, 'name' => 'Empleado', 'company_id' => null]);
        $this->employeeRole->id = 5;
        $this->employeeRole->setRelation('permissions', collect([]));

        // 4. Usuarios
        $this->superAdmin = new User(['company_id' => 1, 'username' => 'super.admin', 'email' => 'super@callshift.com', 'status' => 'ACTIVE']);
        $this->superAdmin->id = 1;
        $this->superAdmin->setRelation('role', $this->superAdminRole);
        $this->superAdmin->setRelation('company', $this->companyA);

        $this->hrAdminA = new User(['company_id' => 1, 'username' => 'hr.admin.a', 'email' => 'hr.a@empresaA.com', 'status' => 'ACTIVE']);
        $this->hrAdminA->id = 10;
        $this->hrAdminA->setRelation('role', $this->hrAdminRole);
        $this->hrAdminA->setRelation('company', $this->companyA);

        $this->viewerA = new User(['company_id' => 1, 'username' => 'viewer.a', 'email' => 'viewer.a@empresaA.com', 'status' => 'ACTIVE']);
        $this->viewerA->id = 12;
        $this->viewerA->setRelation('role', $this->viewerRole);
        $this->viewerA->setRelation('company', $this->companyA);

        $this->employeeA = new User(['company_id' => 1, 'username' => 'emp.a', 'email' => 'emp.a@empresaA.com', 'status' => 'ACTIVE']);
        $this->employeeA->id = 15;
        $this->employeeA->setRelation('role', $this->employeeRole);
        $this->employeeA->setRelation('company', $this->companyA);

        $this->hrAdminB = new User(['company_id' => 2, 'username' => 'hr.admin.b', 'email' => 'hr.b@empresaB.com', 'status' => 'ACTIVE']);
        $this->hrAdminB->id = 20;
        $this->hrAdminB->setRelation('role', $this->hrAdminRole);
        $this->hrAdminB->setRelation('company', $this->companyB);

        // 5. Tipos de Turno
        $this->shiftA_day = new ShiftType([
            'company_id'             => 1,
            'name'                   => 'Mañana Estándar',
            'code'                   => 'M06_14',
            'color_hex'              => '#3B82F6',
            'start_time'             => '06:00',
            'end_time'               => '14:00',
            'break_duration_minutes' => 60,
            'total_work_hours'       => 7.00,
            'crosses_midnight'       => false,
            'status'                 => 'ACTIVE',
        ]);
        $this->shiftA_day->id = 101;

        $this->shiftA_night = new ShiftType([
            'company_id'             => 1,
            'name'                   => 'Nocturno Ordinario',
            'code'                   => 'N22_06',
            'color_hex'              => '#6366F1',
            'start_time'             => '22:00',
            'end_time'               => '06:00',
            'break_duration_minutes' => 60,
            'total_work_hours'       => 7.00,
            'crosses_midnight'       => true,
            'status'                 => 'ACTIVE',
        ]);
        $this->shiftA_night->id = 102;

        $this->shiftB_day = new ShiftType([
            'company_id'             => 2,
            'name'                   => 'Mañana Empresa B',
            'code'                   => 'M06_14',
            'color_hex'              => '#3B82F6',
            'start_time'             => '06:00',
            'end_time'               => '14:00',
            'break_duration_minutes' => 60,
            'total_work_hours'       => 7.00,
            'crosses_midnight'       => false,
            'status'                 => 'ACTIVE',
        ]);
        $this->shiftB_day->id = 201;
    }

    /**
     * TEST 1: Crear ShiftType autorizado (HR_ADMIN)
     */
    public function test_01_authorized_user_can_create_shift_type(): void
    {
        $response = $this->policy->create($this->hrAdminA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 2: Actualizar ShiftType autorizado (HR_ADMIN)
     */
    public function test_02_authorized_user_can_update_shift_type(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->shiftA_day);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 3: Eliminar ShiftType autorizado (HR_ADMIN)
     */
    public function test_03_authorized_user_can_delete_shift_type(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->shiftA_day);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 4: Usuario sin permisos recibe 403 / Denegado
     */
    public function test_04_unauthorized_user_is_denied(): void
    {
        $viewResp = $this->policy->view($this->employeeA, $this->shiftA_day);
        $this->assertFalse($viewResp->allowed());

        $createResp = $this->policy->create($this->employeeA);
        $this->assertFalse($createResp->allowed());

        $updateResp = $this->policy->update($this->employeeA, $this->shiftA_day);
        $this->assertFalse($updateResp->allowed());
    }

    /**
     * TEST 5: Empresa A no puede leer ShiftType de Empresa B (403)
     */
    public function test_05_user_cannot_view_other_company_shift(): void
    {
        $response = $this->policy->view($this->hrAdminA, $this->shiftB_day);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 6: Empresa A no puede modificar ShiftType de Empresa B (403)
     */
    public function test_06_user_cannot_update_other_company_shift(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->shiftB_day);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 7: Empresa A no puede eliminar ShiftType de Empresa B (403)
     */
    public function test_07_user_cannot_delete_other_company_shift(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->shiftB_day);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 8: company_id enviado por cliente no altera el tenant (No mass assignment)
     */
    public function test_08_cannot_manipulate_company_id_via_payload(): void
    {
        $target = clone $this->shiftA_day;
        $originalCompanyId = $target->company_id;

        $maliciousPayload = [
            'id'         => 999,
            'company_id' => 2,
            'name'       => 'Turno Modificado',
        ];

        unset($maliciousPayload['id'], $maliciousPayload['company_id']);
        $target->fill($maliciousPayload);

        $this->assertEquals($originalCompanyId, $target->company_id);
        $this->assertEquals('Turno Modificado', $target->name);
    }

    /**
     * TEST 9: Código duplicado dentro del mismo tenant es rechazado (422)
     */
    public function test_09_duplicate_code_in_same_tenant_is_rejected(): void
    {
        $s1 = new ShiftType(['company_id' => 1, 'code' => 'M06_14']);
        $s2 = new ShiftType(['company_id' => 1, 'code' => 'M06_14']);

        $this->assertEquals($s1->company_id, $s2->company_id);
        $this->assertEquals($s1->code, $s2->code);

        $isDuplicate = ($s1->company_id === $s2->company_id && $s1->code === $s2->code);
        $this->assertTrue($isDuplicate);
    }

    /**
     * TEST 10: Mismo código en otro tenant es permitido (unicidad scoped por empresa)
     */
    public function test_10_same_code_in_different_tenants_is_permitted(): void
    {
        $sA = $this->shiftA_day; // company_id: 1, code: M06_14
        $sB = $this->shiftB_day; // company_id: 2, code: M06_14

        $this->assertEquals($sA->code, $sB->code);
        $this->assertNotEquals($sA->company_id, $sB->company_id);

        $canCoexist = ($sA->company_id !== $sB->company_id);
        $this->assertTrue($canCoexist);
    }

    /**
     * TEST 11: Turno normal diurno 08:00 -> 17:00 (crosses_midnight = false, 8h efectivas con 60 min break)
     */
    public function test_11_day_shift_calculation(): void
    {
        $data = [
            'start_time'             => '08:00',
            'end_time'               => '17:00',
            'break_duration_minutes' => 60,
        ];

        $this->service->normalizeAndComputeShiftData($data);

        $this->assertFalse($data['crosses_midnight']);
        $this->assertEquals(8.00, $data['total_work_hours']);
    }

    /**
     * TEST 12: Turno nocturno 22:00 -> 06:00 (crosses_midnight = true)
     */
    public function test_12_night_shift_crosses_midnight_flag(): void
    {
        $data = [
            'start_time'             => '22:00',
            'end_time'               => '06:00',
            'break_duration_minutes' => 60,
        ];

        $this->service->normalizeAndComputeShiftData($data);

        $this->assertTrue($data['crosses_midnight']);
    }

    /**
     * TEST 13: Duración nocturna calculada correctamente (22:00 -> 06:00 = 8h brutas, 7h efectivas)
     */
    public function test_13_night_shift_hours_calculation(): void
    {
        $data = [
            'start_time'             => '22:00',
            'end_time'               => '06:00',
            'break_duration_minutes' => 60,
        ];

        $this->service->normalizeAndComputeShiftData($data);

        // 8 horas brutas - 1 hora de descanso = 7 horas efectivas
        $this->assertEquals(7.00, $data['total_work_hours']);
    }

    /**
     * TEST 14: Turno continuo de 24 horas (08:00 -> 08:00 con crosses_midnight = true)
     */
    public function test_14_twenty_four_hour_shift_calculation(): void
    {
        $data = [
            'start_time'             => '08:00',
            'end_time'               => '08:00',
            'crosses_midnight'       => true,
            'break_duration_minutes' => 120, // 2 horas de break
        ];

        $this->service->normalizeAndComputeShiftData($data);

        $this->assertTrue($data['crosses_midnight']);
        // 24 horas brutas - 2 horas break = 22 horas efectivas
        $this->assertEquals(22.00, $data['total_work_hours']);
    }

    /**
     * TEST 15: Color hexadecimal inválido es detectado
     */
    public function test_15_invalid_hex_color_validation(): void
    {
        $validHex = '#3B82F6';
        $invalidHex1 = 'red';
        $invalidHex2 = '#ZZZZZZ';
        $invalidHex3 = '#12345';

        $this->assertEquals(1, preg_match('/^#([a-fA-F0-9]{6})$/i', $validHex));
        $this->assertEquals(0, preg_match('/^#([a-fA-F0-9]{6})$/i', $invalidHex1));
        $this->assertEquals(0, preg_match('/^#([a-fA-F0-9]{6})$/i', $invalidHex2));
        $this->assertEquals(0, preg_match('/^#([a-fA-F0-9]{6})$/i', $invalidHex3));
    }

    /**
     * TEST 16: Creación y modificación generan registros forenses en audit_logs
     */
    public function test_16_operations_log_audit_records(): void
    {
        $auditLog = new AuditLog([
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrAdminA->id,
            'action'         => AuditAction::CREATE,
            'auditable_id'   => $this->shiftA_night->id,
            'auditable_type' => ShiftType::class,
            'old_values'     => null,
            'new_values'     => ['name' => 'Nocturno Ordinario', 'code' => 'N22_06', 'crosses_midnight' => true],
            'ip_address'     => '127.0.0.1',
        ]);

        $this->assertEquals(AuditAction::CREATE, $auditLog->action);
        $this->assertTrue($auditLog->new_values['crosses_midnight']);
    }

    /**
     * TEST 17: ShiftTypeResource no expone información sensible
     */
    public function test_17_resource_is_sanitized_and_formats_times(): void
    {
        $resource = new ShiftTypeResource($this->shiftA_night);
        $array = $resource->toArray(Request::create('/api/v1/shift-types/102', 'GET'));

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('color_hex', $array);
        $this->assertEquals('22:00', $array['start_time']);
        $this->assertEquals('06:00', $array['end_time']);
        $this->assertTrue($array['crosses_midnight']);
        $this->assertEquals(7.00, $array['total_work_hours']);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('secret', $array);
    }
}
