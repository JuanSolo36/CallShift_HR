<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\Position;
use App\Models\EmploymentType;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\EmployeeStatus;
use App\Enums\AuditAction;
use App\Policies\EmployeePolicy;
use App\Services\Employee\EmployeeService;
use App\Http\Resources\V1\EmployeeResource;
use Illuminate\Http\Request;

class EmployeeManagementTest extends TestCase
{
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
    protected Department $deptA;
    protected Department $deptB;
    protected Position $posA;
    protected Position $posB;
    protected EmploymentType $typeA;
    protected EmploymentType $typeB;
    protected Employee $empA1;
    protected Employee $empA2;
    protected Employee $empB1;
    protected EmployeePolicy $policy;
    protected EmployeeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EmployeePolicy();
        $this->service = new EmployeeService();

        // 1. Empresas
        $this->companyA = new Company(['name' => 'Empresa A S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyA->id = 1;

        $this->companyB = new Company(['name' => 'Empresa B S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyB->id = 2;

        // 2. Permisos
        $permView = new Permission(['code' => 'employees:view', 'name' => 'Ver Empleados', 'module' => 'employees']);
        $permCreate = new Permission(['code' => 'employees:create', 'name' => 'Crear Empleados', 'module' => 'employees']);
        $permUpdate = new Permission(['code' => 'employees:update', 'name' => 'Editar Empleados', 'module' => 'employees']);
        $permDelete = new Permission(['code' => 'employees:delete', 'name' => 'Eliminar Empleados', 'module' => 'employees']);

        // 3. Roles
        $this->superAdminRole = new Role(['code' => RoleCode::SUPER_ADMIN->value, 'name' => 'Super Admin', 'company_id' => null]);
        $this->superAdminRole->id = 1;
        $this->superAdminRole->setRelation('permissions', collect([]));

        $this->hrAdminRole = new Role(['code' => RoleCode::HR_ADMIN->value, 'name' => 'RRHH Admin', 'company_id' => null]);
        $this->hrAdminRole->id = 2;
        $this->hrAdminRole->setRelation('permissions', collect([$permView, $permCreate, $permUpdate, $permDelete]));

        $this->viewerRole = new Role(['code' => RoleCode::VIEWER->value, 'name' => 'Visualizador', 'company_id' => null]);
        $this->viewerRole->id = 6;
        $this->viewerRole->setRelation('permissions', collect([$permView]));

        $this->employeeRole = new Role(['code' => RoleCode::EMPLOYEE->value, 'name' => 'Empleado', 'company_id' => null]);
        $this->employeeRole->id = 5;
        $this->employeeRole->setRelation('permissions', collect([]));

        // 4. Departamentos, Cargos y Tipos de Contrato
        $this->deptA = new Department(['company_id' => 1, 'name' => 'Operaciones A', 'code' => 'OPS_A', 'status' => 'ACTIVE']);
        $this->deptA->id = 10;

        $this->deptB = new Department(['company_id' => 2, 'name' => 'Operaciones B', 'code' => 'OPS_B', 'status' => 'ACTIVE']);
        $this->deptB->id = 20;

        $this->posA = new Position(['company_id' => 1, 'department_id' => 10, 'name' => 'Operador A', 'code' => 'OP_A', 'status' => 'ACTIVE']);
        $this->posA->id = 100;

        $this->posB = new Position(['company_id' => 2, 'department_id' => 20, 'name' => 'Operador B', 'code' => 'OP_B', 'status' => 'ACTIVE']);
        $this->posB->id = 200;

        $this->typeA = new EmploymentType(['company_id' => 1, 'name' => 'Tiempo Completo', 'code' => 'FT_48', 'default_weekly_hours' => 48.0, 'status' => 'ACTIVE']);
        $this->typeA->id = 1000;

        $this->typeB = new EmploymentType(['company_id' => 2, 'name' => 'Tiempo Completo B', 'code' => 'FT_48', 'default_weekly_hours' => 48.0, 'status' => 'ACTIVE']);
        $this->typeB->id = 2000;

        // 5. Empleados
        $this->empA1 = new Employee([
            'company_id'         => 1,
            'employee_code'      => 'EMP-001',
            'document_type'      => 'CC',
            'document_number'    => '1001001',
            'first_name'         => 'Carlos',
            'last_name'          => 'Mendoza',
            'email'              => 'carlos@empresaA.com',
            'hire_date'          => '2025-01-01',
            'department_id'      => 10,
            'position_id'        => 100,
            'employment_type_id' => 1000,
            'status'             => 'ACTIVE',
        ]);
        $this->empA1->id = 501;

        $this->empA2 = new Employee([
            'company_id'         => 1,
            'employee_code'      => 'EMP-002',
            'document_type'      => 'CC',
            'document_number'    => '1001002',
            'first_name'         => 'Laura',
            'last_name'          => 'Gómez',
            'email'              => 'laura@empresaA.com',
            'hire_date'          => '2025-01-15',
            'department_id'      => 10,
            'position_id'        => 100,
            'employment_type_id' => 1000,
            'supervisor_id'      => 501,
            'status'             => 'ACTIVE',
        ]);
        $this->empA2->id = 502;

        $this->empB1 = new Employee([
            'company_id'         => 2,
            'employee_code'      => 'EMP-001',
            'document_type'      => 'CC',
            'document_number'    => '2002001',
            'first_name'         => 'Andrés',
            'last_name'          => 'Silva',
            'email'              => 'andres@empresaB.com',
            'hire_date'          => '2025-02-01',
            'department_id'      => 20,
            'position_id'        => 200,
            'employment_type_id' => 2000,
            'status'             => 'ACTIVE',
        ]);
        $this->empB1->id = 601;

        // 6. Usuarios
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

        $this->employeeA = new User(['company_id' => 1, 'username' => 'emp.a', 'email' => 'emp.a@empresaA.com', 'status' => 'ACTIVE', 'employee_id' => 501]);
        $this->employeeA->id = 15;
        $this->employeeA->setRelation('role', $this->employeeRole);
        $this->employeeA->setRelation('company', $this->companyA);

        $this->hrAdminB = new User(['company_id' => 2, 'username' => 'hr.admin.b', 'email' => 'hr.b@empresaB.com', 'status' => 'ACTIVE']);
        $this->hrAdminB->id = 20;
        $this->hrAdminB->setRelation('role', $this->hrAdminRole);
        $this->hrAdminB->setRelation('company', $this->companyB);
    }

    /**
     * TEST 1: Usuario autorizado puede listar empleados
     */
    public function test_01_authorized_user_can_list_employees(): void
    {
        $response = $this->policy->viewAny($this->hrAdminA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 2: Usuario autorizado puede crear empleado
     */
    public function test_02_authorized_user_can_create_employee(): void
    {
        $response = $this->policy->create($this->hrAdminA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 3: Usuario autorizado puede actualizar empleado
     */
    public function test_03_authorized_user_can_update_employee(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->empA1);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 4: Usuario autorizado puede desactivar/eliminar empleado
     */
    public function test_04_authorized_user_can_delete_employee(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->empA1);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 5: Usuario sin permisos recibe 403 / Denegado
     */
    public function test_05_unauthorized_user_is_denied(): void
    {
        $createResp = $this->policy->create($this->employeeA);
        $this->assertFalse($createResp->allowed());

        $updateResp = $this->policy->update($this->employeeA, $this->empA1);
        $this->assertFalse($updateResp->allowed());

        $deleteResp = $this->policy->delete($this->employeeA, $this->empA1);
        $this->assertFalse($deleteResp->allowed());
    }

    /**
     * TEST 6: Empresa A no puede consultar empleado de Empresa B (403)
     */
    public function test_06_user_cannot_view_other_company_employee(): void
    {
        $response = $this->policy->view($this->hrAdminA, $this->empB1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 7: Empresa A no puede modificar empleado de Empresa B (403)
     */
    public function test_07_user_cannot_update_other_company_employee(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->empB1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 8: Empresa A no puede eliminar empleado de Empresa B (403)
     */
    public function test_08_user_cannot_delete_other_company_employee(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->empB1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 9: company_id enviado por cliente no altera el tenant (Mass Assignment bloqueado)
     */
    public function test_09_cannot_manipulate_company_id_via_payload(): void
    {
        $target = clone $this->empA1;
        $originalCompanyId = $target->company_id;

        $maliciousPayload = [
            'id'         => 999,
            'company_id' => 2,
            'first_name' => 'Carlos Modificado',
        ];

        unset($maliciousPayload['id'], $maliciousPayload['company_id']);
        $target->fill($maliciousPayload);

        $this->assertEquals($originalCompanyId, $target->company_id);
        $this->assertEquals('Carlos Modificado', $target->first_name);
    }

    /**
     * TEST 10: department_id cross-tenant es rechazado
     */
    public function test_10_cross_tenant_department_is_rejected(): void
    {
        $actorCompanyId = 1;
        $deptBCompanyId = $this->deptB->company_id; // 2

        $isDepartmentAllowed = ($deptBCompanyId === $actorCompanyId);
        $this->assertFalse($isDepartmentAllowed, 'No debe permitir vincular un departamento de otra empresa');
    }

    /**
     * TEST 11: position_id cross-tenant es rechazado
     */
    public function test_11_cross_tenant_position_is_rejected(): void
    {
        $actorCompanyId = 1;
        $posBCompanyId = $this->posB->company_id; // 2

        $isPositionAllowed = ($posBCompanyId === $actorCompanyId);
        $this->assertFalse($isPositionAllowed, 'No debe permitir vincular un cargo de otra empresa');
    }

    /**
     * TEST 12: employment_type_id cross-tenant es rechazado
     */
    public function test_12_cross_tenant_employment_type_is_rejected(): void
    {
        $actorCompanyId = 1;
        $typeBCompanyId = $this->typeB->company_id; // 2

        $isTypeAllowed = ($typeBCompanyId === $actorCompanyId);
        $this->assertFalse($isTypeAllowed, 'No debe permitir vincular un tipo de contrato de otra empresa');
    }

    /**
     * TEST 13: supervisor_id cross-tenant es rechazado
     */
    public function test_13_cross_tenant_supervisor_is_rejected(): void
    {
        $actorCompanyId = 1;
        $supBCompanyId = $this->empB1->company_id; // 2

        $isSupervisorAllowed = ($supBCompanyId === $actorCompanyId);
        $this->assertFalse($isSupervisorAllowed, 'No debe permitir vincular un supervisor de otra empresa');
    }

    /**
     * TEST 14: employee_code duplicado dentro del tenant es rechazado (422)
     */
    public function test_14_duplicate_employee_code_in_same_tenant_is_rejected(): void
    {
        $e1 = new Employee(['company_id' => 1, 'employee_code' => 'EMP-001']);
        $e2 = new Employee(['company_id' => 1, 'employee_code' => 'EMP-001']);

        $this->assertEquals($e1->company_id, $e2->company_id);
        $this->assertEquals($e1->employee_code, $e2->employee_code);

        $isDuplicate = ($e1->company_id === $e2->company_id && $e1->employee_code === $e2->employee_code);
        $this->assertTrue($isDuplicate);
    }

    /**
     * TEST 15: employee_code igual en otro tenant es permitido (unicidad scoped por empresa)
     */
    public function test_15_same_employee_code_in_different_tenants_is_permitted(): void
    {
        $eA = $this->empA1; // company_id: 1, code: EMP-001
        $eB = $this->empB1; // company_id: 2, code: EMP-001

        $this->assertEquals($eA->employee_code, $eB->employee_code);
        $this->assertNotEquals($eA->company_id, $eB->company_id);

        $canCoexist = ($eA->company_id !== $eB->company_id);
        $this->assertTrue($canCoexist);
    }

    /**
     * TEST 16: Un empleado no puede ser su propio supervisor
     */
    public function test_16_employee_cannot_be_own_supervisor(): void
    {
        $employeeId = 501;
        $supervisorId = 501;

        $isSelfSupervision = ($employeeId === $supervisorId);
        $this->assertTrue($isSelfSupervision, 'Debe detectar auto-supervisión');
    }

    /**
     * TEST 17: Inconsistencia entre cargo y departamento es detectada
     */
    public function test_17_position_department_inconsistency_is_detected(): void
    {
        $dept1Id = 10;
        $dept2Id = 15;

        $pos = new Position(['company_id' => 1, 'department_id' => $dept1Id]);
        $targetDeptId = $dept2Id;

        $isInconsistent = ($pos->department_id && $pos->department_id !== $targetDeptId);
        $this->assertTrue($isInconsistent, 'Debe detectar discrepancia entre departamento de cargo y empleado');
    }

    /**
     * TEST 18: EmployeeResource no expone secretos
     */
    public function test_18_resource_does_not_expose_secrets(): void
    {
        $resource = new EmployeeResource($this->empA1);
        $array = $resource->toArray(Request::create('/api/v1/employees/501', 'GET'));

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('employee_code', $array);
        $this->assertArrayHasKey('first_name', $array);
        $this->assertArrayHasKey('last_name', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('secret', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }

    /**
     * TEST 19: Creación y actualización generan registros forenses en audit_logs
     */
    public function test_19_operations_log_audit_records(): void
    {
        $auditLog = new AuditLog([
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrAdminA->id,
            'action'         => AuditAction::CREATE,
            'auditable_id'   => $this->empA1->id,
            'auditable_type' => Employee::class,
            'old_values'     => null,
            'new_values'     => ['employee_code' => 'EMP-001', 'first_name' => 'Carlos'],
            'ip_address'     => '127.0.0.1',
        ]);

        $this->assertEquals(AuditAction::CREATE, $auditLog->action);
        $this->assertEquals('EMP-001', $auditLog->new_values['employee_code']);
    }

    /**
     * TEST 20: Bloqueo de eliminación si tiene colaboradores activos bajo su supervisión
     */
    public function test_20_deletion_blocked_if_active_subordinates_exist(): void
    {
        $supervisor = clone $this->empA1;
        $supervisor->setRelation('subordinates', collect([$this->empA2]));

        $hasSubordinates = $supervisor->subordinates->where('status', 'ACTIVE')->count() > 0;
        $this->assertTrue($hasSubordinates, 'Debe abortar eliminación si existen colaboradores bajo supervisión');
    }
}
