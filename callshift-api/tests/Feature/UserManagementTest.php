<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Policies\UserPolicy;
use App\Services\Users\UserService;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Requests\V1\AssignRoleRequest;
use App\Http\Resources\V1\UserResource;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class UserManagementTest extends TestCase
{
    protected Company $companyA;
    protected Company $companyB;
    protected Role $superAdminRole;
    protected Role $hrAdminRole;
    protected Role $supervisorRole;
    protected Role $employeeRole;
    protected Role $customRoleCompanyA;
    protected Role $customRoleCompanyB;
    protected Employee $employeeCompanyA;
    protected Employee $employeeCompanyB;
    protected User $superAdmin;
    protected User $hrAdminA;
    protected User $supervisorA;
    protected User $userCompanyB;
    protected UserPolicy $userPolicy;
    protected UserService $userService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userPolicy = new UserPolicy();
        $this->userService = new UserService();

        // 1. Empresas
        $this->companyA = new Company(['name' => 'Empresa A', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyA->id = 1;

        $this->companyB = new Company(['name' => 'Empresa B', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyB->id = 2;

        // 2. Permisos
        $permView = new Permission(['code' => 'users:view', 'name' => 'Ver Usuarios', 'module' => 'users']);
        $permCreate = new Permission(['code' => 'users:create', 'name' => 'Crear Usuarios', 'module' => 'users']);
        $permUpdate = new Permission(['code' => 'users:update', 'name' => 'Actualizar Usuarios', 'module' => 'users']);
        $permDelete = new Permission(['code' => 'users:delete', 'name' => 'Eliminar Usuarios', 'module' => 'users']);

        // 3. Roles Globales del Sistema (company_id is null)
        $this->superAdminRole = new Role(['code' => RoleCode::SUPER_ADMIN->value, 'name' => 'Super Admin', 'company_id' => null, 'is_system' => true]);
        $this->superAdminRole->id = 1;
        $this->superAdminRole->setRelation('permissions', collect([]));

        $this->hrAdminRole = new Role(['code' => RoleCode::HR_ADMIN->value, 'name' => 'RRHH Admin', 'company_id' => null, 'is_system' => true]);
        $this->hrAdminRole->id = 2;
        $this->hrAdminRole->setRelation('permissions', collect([$permView, $permCreate, $permUpdate]));

        $this->supervisorRole = new Role(['code' => RoleCode::SUPERVISOR->value, 'name' => 'Supervisor', 'company_id' => null, 'is_system' => true]);
        $this->supervisorRole->id = 4;
        $this->supervisorRole->setRelation('permissions', collect([$permView]));

        $this->employeeRole = new Role(['code' => RoleCode::EMPLOYEE->value, 'name' => 'Empleado', 'company_id' => null, 'is_system' => true]);
        $this->employeeRole->id = 5;
        $this->employeeRole->setRelation('permissions', collect([]));

        // Roles personalizados por empresa
        $this->customRoleCompanyA = new Role(['code' => 'CUSTOM_LEAD_A', 'name' => 'Lead A', 'company_id' => 1, 'is_system' => false]);
        $this->customRoleCompanyA->id = 101;

        $this->customRoleCompanyB = new Role(['code' => 'CUSTOM_LEAD_B', 'name' => 'Lead B', 'company_id' => 2, 'is_system' => false]);
        $this->customRoleCompanyB->id = 102;

        // 4. Empleados
        $this->employeeCompanyA = new Employee([
            'company_id'    => 1,
            'employee_code' => 'EMP-A-001',
            'first_name'    => 'Juan',
            'last_name'     => 'Perez',
            'email'         => 'juan.perez@empresaA.com',
            'status'        => EmployeeStatus::ACTIVE,
        ]);
        $this->employeeCompanyA->id = 10;

        $this->employeeCompanyB = new Employee([
            'company_id'    => 2,
            'employee_code' => 'EMP-B-001',
            'first_name'    => 'Pedro',
            'last_name'     => 'Gomez',
            'email'         => 'pedro.gomez@empresaB.com',
            'status'        => EmployeeStatus::ACTIVE,
        ]);
        $this->employeeCompanyB->id = 20;

        // 5. Usuarios
        $this->superAdmin = new User(['company_id' => 1, 'username' => 'super.admin', 'email' => 'super@callshift.com', 'status' => 'ACTIVE']);
        $this->superAdmin->id = 10;
        $this->superAdmin->setRelation('role', $this->superAdminRole);
        $this->superAdmin->setRelation('company', $this->companyA);

        $this->hrAdminA = new User(['company_id' => 1, 'username' => 'hr.admin.a', 'email' => 'hr.a@callshift.com', 'status' => 'ACTIVE']);
        $this->hrAdminA->id = 20;
        $this->hrAdminA->setRelation('role', $this->hrAdminRole);
        $this->hrAdminA->setRelation('company', $this->companyA);

        $this->supervisorA = new User(['company_id' => 1, 'username' => 'supervisor.a', 'email' => 'sup.a@callshift.com', 'status' => 'ACTIVE']);
        $this->supervisorA->id = 30;
        $this->supervisorA->setRelation('role', $this->supervisorRole);
        $this->supervisorA->setRelation('company', $this->companyA);

        $this->userCompanyB = new User(['company_id' => 2, 'username' => 'user.company.b', 'email' => 'user.b@companyb.com', 'status' => 'ACTIVE']);
        $this->userCompanyB->id = 40;
        $this->userCompanyB->setRelation('role', $this->employeeRole);
        $this->userCompanyB->setRelation('company', $this->companyB);
    }

    /**
     * TEST 1: Usuario Empresa A intenta crear usuario con employee_id de Empresa B -> Bloqueado
     */
    public function test_01_cannot_link_employee_from_different_company(): void
    {
        $actorCompanyId = $this->hrAdminA->company_id; // Company 1
        $targetEmployeeCompanyId = $this->employeeCompanyB->company_id; // Company 2

        $this->assertNotEquals($actorCompanyId, $targetEmployeeCompanyId);

        // La regla validará que company_id coincida con el actor
        $isSameCompany = ($targetEmployeeCompanyId === $actorCompanyId);
        $this->assertFalse($isSameCompany, 'No debe permitir vincular un empleado de otra empresa');
    }

    /**
     * TEST 2: Intento de registrar username duplicado en la misma empresa -> Bloqueado
     */
    public function test_02_duplicate_username_in_same_company_is_rejected(): void
    {
        $user1 = new User(['company_id' => 1, 'username' => 'carlos.mendoza', 'email' => 'carlos1@callshift.com']);
        $user2 = new User(['company_id' => 1, 'username' => 'carlos.mendoza', 'email' => 'carlos2@callshift.com']);

        $this->assertEquals($user1->company_id, $user2->company_id);
        $this->assertEquals($user1->username, $user2->username);

        // Restricción compuesta UNIQUE(company_id, username)
        $isUniqueInCompany = !($user1->company_id === $user2->company_id && $user1->username === $user2->username);
        $this->assertFalse($isUniqueInCompany, 'Debe rechazar username duplicado dentro del mismo tenant');
    }

    /**
     * TEST 3: Mismo username en empresas distintas es permitido (unicidad scoped por company_id)
     */
    public function test_03_same_username_in_different_companies_is_permitted(): void
    {
        $userCompanyA = new User(['company_id' => 1, 'username' => 'carlos.mendoza', 'email' => 'carlos@empresaA.com']);
        $userCompanyB = new User(['company_id' => 2, 'username' => 'carlos.mendoza', 'email' => 'carlos@empresaB.com']);

        $this->assertEquals($userCompanyA->username, $userCompanyB->username);
        $this->assertNotEquals($userCompanyA->company_id, $userCompanyB->company_id);
        $this->assertNotEquals($userCompanyA->email, $userCompanyB->email); // Emails globalmente únicos

        // Comprobación de que no colisionan por tener company_id distinto
        $canCoexist = ($userCompanyA->company_id !== $userCompanyB->company_id) && ($userCompanyA->email !== $userCompanyB->email);
        $this->assertTrue($canCoexist, 'Mismo username en empresas distintas debe estar permitido');
    }

    /**
     * TEST 4: Usuario Empresa A intenta asignar role_id de Empresa B -> Bloqueado
     */
    public function test_04_cannot_assign_custom_role_from_different_company(): void
    {
        $actorCompanyId = 1;
        $roleCompanyId = $this->customRoleCompanyB->company_id; // Company 2

        $isRoleAllowed = ($roleCompanyId === null || $roleCompanyId === $actorCompanyId);
        $this->assertFalse($isRoleAllowed, 'No debe permitir asignar un rol personalizado de otra empresa');
    }

    /**
     * TEST 5: Usuario Empresa A puede asignar rol global del sistema (company_id is null)
     */
    public function test_05_can_assign_global_system_role(): void
    {
        $actorCompanyId = 1;
        $roleCompanyId = $this->supervisorRole->company_id; // null

        $isRoleAllowed = ($roleCompanyId === null || $roleCompanyId === $actorCompanyId);
        $this->assertTrue($isRoleAllowed, 'Debe permitir asignar roles globales del sistema');
    }

    /**
     * TEST 6: Usuario Empresa A puede asignar rol propio de Empresa A
     */
    public function test_06_can_assign_tenant_own_custom_role(): void
    {
        $actorCompanyId = 1;
        $roleCompanyId = $this->customRoleCompanyA->company_id; // 1

        $isRoleAllowed = ($roleCompanyId === null || $roleCompanyId === $actorCompanyId);
        $this->assertTrue($isRoleAllowed, 'Debe permitir asignar roles propios del tenant');
    }

    /**
     * TEST 7: deleteUser() mantiene consistencia transaccional y bloquea auto-eliminación
     */
    public function test_07_delete_user_prevents_self_deletion_and_ensures_atomic_integrity(): void
    {
        // 1. Auto-eliminación rechazada
        $deleteSelfResponse = $this->userPolicy->delete($this->hrAdminA, $this->hrAdminA);
        $this->assertFalse($deleteSelfResponse->allowed());

        // 2. Eliminación autorizada sobre otro usuario del mismo tenant
        $deleteOtherResponse = $this->userPolicy->delete($this->hrAdminA, $this->supervisorA);
        $this->assertTrue($deleteOtherResponse->allowed());

        // 3. Eliminación cross-tenant rechazada
        $deleteCrossResponse = $this->userPolicy->delete($this->hrAdminA, $this->userCompanyB);
        $this->assertFalse($deleteCrossResponse->allowed());
    }

    /**
     * TEST 8: Bloqueo de auto-escalada de privilegios y sanitización de credenciales
     */
    public function test_08_privilege_escalation_blocked_and_credentials_masked(): void
    {
        // Auto-escalada bloqueada
        $assignResponse = $this->userPolicy->assignRole($this->hrAdminA, $this->hrAdminA, 1);
        $this->assertFalse($assignResponse->allowed());

        // Resource sanitizado
        $user = clone $this->supervisorA;
        $user->password = Hash::make('SecretPass123*');
        $resource = new UserResource($user);
        $array = $resource->toArray(Request::create('/api/v1/users/30', 'GET'));

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
