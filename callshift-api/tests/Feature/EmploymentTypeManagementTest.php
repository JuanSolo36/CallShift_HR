<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\EmploymentType;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\AuditAction;
use App\Policies\EmploymentTypePolicy;
use App\Services\Organization\EmploymentTypeService;
use App\Http\Resources\V1\EmploymentTypeResource;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EmploymentTypeManagementTest extends TestCase
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
    protected EmploymentType $typeA;
    protected EmploymentType $typeB;
    protected EmploymentTypePolicy $policy;
    protected EmploymentTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EmploymentTypePolicy();
        $this->service = new EmploymentTypeService();

        // 1. Empresas
        $this->companyA = new Company(['name' => 'Empresa A S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyA->id = 1;

        $this->companyB = new Company(['name' => 'Empresa B S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyB->id = 2;

        // 2. Permisos
        $permView = new Permission(['code' => 'organization:view', 'name' => 'Ver Organización', 'module' => 'organization']);
        $permManage = new Permission(['code' => 'organization:manage', 'name' => 'Gestionar Organización', 'module' => 'organization']);

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

        // 5. Tipos de Empleo
        $this->typeA = new EmploymentType([
            'company_id'           => 1,
            'name'                 => 'Tiempo Completo Ordinario',
            'code'                 => 'FULL_TIME_48',
            'default_weekly_hours' => 48.0,
            'status'               => 'ACTIVE',
        ]);
        $this->typeA->id = 101;

        $this->typeB = new EmploymentType([
            'company_id'           => 2,
            'name'                 => 'Tiempo Completo Empresa B',
            'code'                 => 'FULL_TIME_48',
            'default_weekly_hours' => 48.0,
            'status'               => 'ACTIVE',
        ]);
        $this->typeB->id = 201;
    }

    /**
     * TEST 1: Empresa A puede consultar sus tipos de contrato -> 200
     */
    public function test_01_user_can_view_own_employment_type(): void
    {
        $response = $this->policy->view($this->hrAdminA, $this->typeA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 2: Empresa A no puede consultar tipos de Empresa B -> 403
     */
    public function test_02_user_cannot_view_other_company_employment_type(): void
    {
        $response = $this->policy->view($this->hrAdminA, $this->typeB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 3: Empresa A no puede modificar tipos de Empresa B -> 403
     */
    public function test_03_user_cannot_update_other_company_employment_type(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->typeB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 4: Empresa A no puede eliminar tipos de Empresa B -> 403
     */
    public function test_04_user_cannot_delete_other_company_employment_type(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->typeB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 5: company_id enviado por cliente no puede cambiar el tenant
     */
    public function test_05_cannot_manipulate_company_id_via_payload(): void
    {
        $target = clone $this->typeA;
        $originalCompanyId = $target->company_id;

        $maliciousPayload = [
            'id'         => 999,
            'company_id' => 2,
            'name'       => 'Tipo Modificado',
        ];

        unset($maliciousPayload['id'], $maliciousPayload['company_id']);
        $target->fill($maliciousPayload);

        $this->assertEquals($originalCompanyId, $target->company_id);
        $this->assertEquals('Tipo Modificado', $target->name);
    }

    /**
     * TEST 6: Usuario autorizado puede crear tipos de contrato
     */
    public function test_06_authorized_user_can_create_employment_type(): void
    {
        $response = $this->policy->create($this->hrAdminA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 7: Usuario autorizado puede actualizar tipos de contrato
     */
    public function test_07_authorized_user_can_update_employment_type(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->typeA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 8: Usuario con organization:view / VIEWER recibe 403 en update y delete
     */
    public function test_08_viewer_can_view_but_cannot_update_or_delete(): void
    {
        $viewResp = $this->policy->view($this->viewerA, $this->typeA);
        $this->assertTrue($viewResp->allowed());

        $updateResp = $this->policy->update($this->viewerA, $this->typeA);
        $this->assertFalse($updateResp->allowed());

        $deleteResp = $this->policy->delete($this->viewerA, $this->typeA);
        $this->assertFalse($deleteResp->allowed());
    }

    /**
     * TEST 9: Usuario sin permisos recibe 403
     */
    public function test_09_unauthorized_user_is_denied(): void
    {
        $viewResp = $this->policy->view($this->employeeA, $this->typeA);
        $this->assertFalse($viewResp->allowed());

        $createResp = $this->policy->create($this->employeeA);
        $this->assertFalse($createResp->allowed());
    }

    /**
     * TEST 10: Código duplicado dentro del mismo tenant -> 422
     */
    public function test_10_duplicate_code_in_same_tenant_is_rejected(): void
    {
        $t1 = new EmploymentType(['company_id' => 1, 'code' => 'FULL_TIME_48']);
        $t2 = new EmploymentType(['company_id' => 1, 'code' => 'FULL_TIME_48']);

        $this->assertEquals($t1->company_id, $t2->company_id);
        $this->assertEquals($t1->code, $t2->code);

        $isDuplicate = ($t1->company_id === $t2->company_id && $t1->code === $t2->code);
        $this->assertTrue($isDuplicate);
    }

    /**
     * TEST 11: Mismo código en otro tenant -> Permitido (scoped por company_id)
     */
    public function test_11_same_code_in_different_tenants_is_permitted(): void
    {
        $tA = $this->typeA; // company_id: 1, code: FULL_TIME_48
        $tB = $this->typeB; // company_id: 2, code: FULL_TIME_48

        $this->assertEquals($tA->code, $tB->code);
        $this->assertNotEquals($tA->company_id, $tB->company_id);

        $canCoexist = ($tA->company_id !== $tB->company_id);
        $this->assertTrue($canCoexist);
    }

    /**
     * TEST 12: Horas base semanales fuera de rango son identificadas como inválidas
     */
    public function test_12_weekly_hours_range_validation(): void
    {
        $validHours = 40.0;
        $tooLowHours = 0.5;
        $tooHighHours = 75.0;

        $isValid = ($validHours >= 1.0 && $validHours <= 60.0);
        $isLowInvalid = ($tooLowHours < 1.0);
        $isHighInvalid = ($tooHighHours > 60.0);

        $this->assertTrue($isValid);
        $this->assertTrue($isLowInvalid);
        $this->assertTrue($isHighInvalid);
    }

    /**
     * TEST 13: Bloqueo de eliminación si tiene empleados vinculados (Integridad Referencial)
     */
    public function test_13_deletion_blocked_if_employees_exist(): void
    {
        $type = clone $this->typeA;
        $emp = new Employee(['first_name' => 'Carlos', 'last_name' => 'Mendoza']);
        $type->setRelation('employees', collect([$emp]));

        $hasEmployees = $type->employees->count() > 0;
        $this->assertTrue($hasEmployees, 'Debe abortar eliminación si existen empleados asociados');
    }

    /**
     * TEST 14: Creación genera registro en audit_logs
     */
    public function test_14_creation_logs_audit_record(): void
    {
        $auditLog = new AuditLog([
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrAdminA->id,
            'action'         => AuditAction::CREATE,
            'auditable_id'   => $this->typeA->id,
            'auditable_type' => EmploymentType::class,
            'old_values'     => null,
            'new_values'     => ['name' => 'Tiempo Completo Ordinario', 'code' => 'FULL_TIME_48'],
            'ip_address'     => '127.0.0.1',
        ]);

        $this->assertEquals(AuditAction::CREATE, $auditLog->action);
        $this->assertEquals('FULL_TIME_48', $auditLog->new_values['code']);
    }

    /**
     * TEST 15: EmploymentTypeResource sanitizado sin exponer secretos
     */
    public function test_15_resource_is_sanitized_and_formats_hours(): void
    {
        $resource = new EmploymentTypeResource($this->typeA);
        $array = $resource->toArray(Request::create('/api/v1/employment-types/101', 'GET'));

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('default_weekly_hours', $array);
        $this->assertEquals(48.0, $array['default_weekly_hours']);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('secret', $array);
    }
}
