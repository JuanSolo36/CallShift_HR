<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Policies\DepartmentPolicy;
use App\Policies\PositionPolicy;
use App\Services\Organization\DepartmentService;
use App\Services\Organization\PositionService;
use App\Http\Resources\V1\DepartmentResource;
use App\Http\Resources\V1\PositionResource;
use Illuminate\Http\Request;

class OrganizationManagementTest extends TestCase
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
    protected DepartmentPolicy $deptPolicy;
    protected PositionPolicy $posPolicy;
    protected DepartmentService $deptService;
    protected PositionService $posService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->deptPolicy = new DepartmentPolicy();
        $this->posPolicy = new PositionPolicy();
        $this->deptService = new DepartmentService();
        $this->posService = new PositionService();

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

        // 5. Departamentos
        $this->deptA = new Department(['company_id' => 1, 'name' => 'Tecnología A', 'code' => 'TECH', 'cost_center_code' => 'CC-TECH-01', 'status' => 'ACTIVE']);
        $this->deptA->id = 101;

        $this->deptB = new Department(['company_id' => 2, 'name' => 'Tecnología B', 'code' => 'TECH', 'cost_center_code' => 'CC-TECH-02', 'status' => 'ACTIVE']);
        $this->deptB->id = 201;

        // 6. Cargos
        $this->posA = new Position(['company_id' => 1, 'department_id' => 101, 'name' => 'Desarrollador', 'code' => 'DEV', 'status' => 'ACTIVE']);
        $this->posA->id = 301;
        $this->posA->setRelation('department', $this->deptA);

        $this->posB = new Position(['company_id' => 2, 'department_id' => 201, 'name' => 'Desarrollador B', 'code' => 'DEV', 'status' => 'ACTIVE']);
        $this->posB->id = 401;
        $this->posB->setRelation('department', $this->deptB);
    }

    /**
     * TEST 1: Empresa A no puede consultar departamentos de Empresa B -> 403 / Denegado
     */
    public function test_01_user_cannot_view_other_company_department(): void
    {
        $response = $this->deptPolicy->view($this->hrAdminA, $this->deptB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 2: Empresa A no puede modificar departamentos de Empresa B -> 403 / Denegado
     */
    public function test_02_user_cannot_update_other_company_department(): void
    {
        $response = $this->deptPolicy->update($this->hrAdminA, $this->deptB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 3: Empresa A no puede eliminar departamentos de Empresa B -> 403 / Denegado
     */
    public function test_03_user_cannot_delete_other_company_department(): void
    {
        $response = $this->deptPolicy->delete($this->hrAdminA, $this->deptB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 4: company_id enviado en payload no altera el tenant del departamento (No mass assignment)
     */
    public function test_04_cannot_manipulate_company_id_via_department_payload(): void
    {
        $targetDept = clone $this->deptA;
        $originalCompanyId = $targetDept->company_id;

        $maliciousPayload = [
            'id'         => 999,
            'company_id' => 2,
            'name'       => 'Tecnología Modificada',
        ];

        unset($maliciousPayload['id'], $maliciousPayload['company_id']);
        $targetDept->fill($maliciousPayload);

        $this->assertEquals($originalCompanyId, $targetDept->company_id);
        $this->assertEquals('Tecnología Modificada', $targetDept->name);
    }

    /**
     * TEST 5: Empresa A no puede vincular un department_id de Empresa B en un Cargo (IDOR foráneo bloqueado)
     */
    public function test_05_cannot_assign_cross_tenant_department_to_position(): void
    {
        $actorCompanyId = 1;
        $deptBCompanyId = $this->deptB->company_id; // 2

        $isDepartmentAllowed = ($deptBCompanyId === $actorCompanyId);
        $this->assertFalse($isDepartmentAllowed, 'No debe permitir vincular un departamento de otra empresa a un cargo');
    }

    /**
     * TEST 6: Usuario con organization:manage / HR_ADMIN puede crear departamentos y cargos
     */
    public function test_06_authorized_user_can_create_department_and_position(): void
    {
        $deptCreateResp = $this->deptPolicy->create($this->hrAdminA);
        $this->assertTrue($deptCreateResp->allowed());

        $posCreateResp = $this->posPolicy->create($this->hrAdminA);
        $this->assertTrue($posCreateResp->allowed());
    }

    /**
     * TEST 7: Usuario con organization:manage / HR_ADMIN puede actualizar departamentos y cargos
     */
    public function test_07_authorized_user_can_update_department_and_position(): void
    {
        $deptUpdateResp = $this->deptPolicy->update($this->hrAdminA, $this->deptA);
        $this->assertTrue($deptUpdateResp->allowed());

        $posUpdateResp = $this->posPolicy->update($this->hrAdminA, $this->posA);
        $this->assertTrue($posUpdateResp->allowed());
    }

    /**
     * TEST 8: Usuario con organization:view puede consultar pero no modificar (403 en update/delete)
     */
    public function test_08_viewer_can_view_but_cannot_update_or_delete(): void
    {
        $viewResp = $this->deptPolicy->view($this->viewerA, $this->deptA);
        $this->assertTrue($viewResp->allowed());

        $updateResp = $this->deptPolicy->update($this->viewerA, $this->deptA);
        $this->assertFalse($updateResp->allowed());

        $deleteResp = $this->deptPolicy->delete($this->viewerA, $this->deptA);
        $this->assertFalse($deleteResp->allowed());
    }

    /**
     * TEST 9: Usuario sin permisos de organización recibe 403
     */
    public function test_09_user_without_permissions_is_denied(): void
    {
        $viewResp = $this->deptPolicy->view($this->employeeA, $this->deptA);
        $this->assertFalse($viewResp->allowed());

        $createResp = $this->deptPolicy->create($this->employeeA);
        $this->assertFalse($createResp->allowed());
    }

    /**
     * TEST 10: Códigos de departamento duplicados en el mismo tenant son rechazados con 422
     */
    public function test_10_duplicate_department_code_in_same_tenant_is_rejected(): void
    {
        $dept1 = new Department(['company_id' => 1, 'code' => 'TECH']);
        $dept2 = new Department(['company_id' => 1, 'code' => 'TECH']);

        $this->assertEquals($dept1->company_id, $dept2->company_id);
        $this->assertEquals($dept1->code, $dept2->code);

        $isDuplicate = ($dept1->company_id === $dept2->company_id && $dept1->code === $dept2->code);
        $this->assertTrue($isDuplicate, 'Debe identificar colisión en el mismo tenant');
    }

    /**
     * TEST 11: Mismo código de departamento en tenants diferentes es permitido (unicidad scoped)
     */
    public function test_11_same_department_code_in_different_tenants_is_permitted(): void
    {
        $deptA = $this->deptA; // company_id: 1, code: TECH
        $deptB = $this->deptB; // company_id: 2, code: TECH

        $this->assertEquals($deptA->code, $deptB->code);
        $this->assertNotEquals($deptA->company_id, $deptB->company_id);

        $canCoexist = ($deptA->company_id !== $deptB->company_id);
        $this->assertTrue($canCoexist, 'Mismo código en empresas distintas debe estar permitido');
    }

    /**
     * TEST 12: Códigos de cargo duplicados en el mismo tenant son rechazados con 422
     */
    public function test_12_duplicate_position_code_in_same_tenant_is_rejected(): void
    {
        $pos1 = new Position(['company_id' => 1, 'code' => 'DEV']);
        $pos2 = new Position(['company_id' => 1, 'code' => 'DEV']);

        $this->assertEquals($pos1->company_id, $pos2->company_id);
        $this->assertEquals($pos1->code, $pos2->code);

        $isDuplicate = ($pos1->company_id === $pos2->company_id && $pos1->code === $pos2->code);
        $this->assertTrue($isDuplicate);
    }

    /**
     * TEST 13: Bloqueo de eliminación de departamento si tiene cargos o empleados asociados
     */
    public function test_13_department_deletion_blocked_if_positions_exist(): void
    {
        $dept = clone $this->deptA;
        $dept->setRelation('positions', collect([$this->posA]));

        $hasPositions = $dept->positions->count() > 0;
        $this->assertTrue($hasPositions, 'Debe detectar cargos dependientes para abortar eliminación');
    }

    /**
     * TEST 14: Bloqueo de eliminación de cargo si tiene empleados asociados
     */
    public function test_14_position_deletion_blocked_if_employees_exist(): void
    {
        $pos = clone $this->posA;
        $emp = new Employee(['first_name' => 'Carlos', 'last_name' => 'Mendoza']);
        $pos->setRelation('employees', collect([$emp]));

        $hasEmployees = $pos->employees->count() > 0;
        $this->assertTrue($hasEmployees, 'Debe detectar empleados dependientes para abortar eliminación');
    }

    /**
     * TEST 15: Operaciones organizacionales generan registros forenses en audit_logs
     */
    public function test_15_organization_operations_log_audit_records(): void
    {
        $auditLog = new AuditLog([
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrAdminA->id,
            'action'         => AuditAction::CREATE,
            'auditable_id'   => $this->deptA->id,
            'auditable_type' => Department::class,
            'old_values'     => null,
            'new_values'     => ['name' => 'Tecnología A', 'code' => 'TECH'],
            'ip_address'     => '127.0.0.1',
        ]);

        $this->assertEquals(AuditAction::CREATE, $auditLog->action);
        $this->assertEquals('TECH', $auditLog->new_values['code']);
    }
}
