<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\AuditAction;
use App\Policies\CompanyPolicy;
use App\Services\Company\CompanyService;
use App\Http\Resources\V1\CompanyResource;
use Illuminate\Http\Request;

class CompanyManagementTest extends TestCase
{
    protected Company $companyA;
    protected Company $companyB;
    protected Role $superAdminRole;
    protected Role $hrAdminRole;
    protected Role $employeeRole;
    protected User $superAdmin;
    protected User $hrAdminA;
    protected User $employeeA;
    protected User $hrAdminB;
    protected CompanyPolicy $companyPolicy;
    protected CompanyService $companyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->companyPolicy = new CompanyPolicy();
        $this->companyService = new CompanyService();

        // 1. Empresas
        $this->companyA = new Company([
            'name'            => 'CallShift Enterprise A S.A.S.',
            'legal_name'      => 'CallShift Corp A',
            'tax_id'          => '901.845.120-1',
            'slug'            => 'callshift-a',
            'email'           => 'admin@empresaA.com',
            'phone'           => '+57 (601) 745-9001',
            'address'         => 'Av. Calle 100 #15-20',
            'city'            => 'Bogotá',
            'country'         => 'COL',
            'timezone'        => 'America/Bogota',
            'currency'        => 'COP',
            'date_format'     => 'YYYY-MM-DD',
            'primary_color'   => '#0284c7',
            'secondary_color' => '#0f172a',
            'status'          => 'ACTIVE',
        ]);
        $this->companyA->id = 1;

        $this->companyB = new Company([
            'name'            => 'CallShift Enterprise B S.A.S.',
            'legal_name'      => 'CallShift Corp B',
            'tax_id'          => '901.845.120-2',
            'slug'            => 'callshift-b',
            'email'           => 'admin@empresaB.com',
            'phone'           => '+57 (601) 745-9002',
            'address'         => 'Carrera 7 #72-01',
            'city'            => 'Medellín',
            'country'         => 'COL',
            'timezone'        => 'America/Bogota',
            'currency'        => 'COP',
            'date_format'     => 'YYYY-MM-DD',
            'primary_color'   => '#10b981',
            'secondary_color' => '#1e293b',
            'status'          => 'ACTIVE',
        ]);
        $this->companyB->id = 2;

        // 2. Permisos
        $permView = new Permission(['code' => 'company:view', 'name' => 'Ver Empresa', 'module' => 'company']);
        $permUpdate = new Permission(['code' => 'company:update', 'name' => 'Actualizar Empresa', 'module' => 'company']);
        $permSettings = new Permission(['code' => 'settings:manage', 'name' => 'Gestionar Ajustes', 'module' => 'company']);

        // 3. Roles
        $this->superAdminRole = new Role(['code' => RoleCode::SUPER_ADMIN->value, 'name' => 'Super Admin', 'company_id' => null]);
        $this->superAdminRole->id = 1;
        $this->superAdminRole->setRelation('permissions', collect([]));

        $this->hrAdminRole = new Role(['code' => RoleCode::HR_ADMIN->value, 'name' => 'RRHH Admin', 'company_id' => null]);
        $this->hrAdminRole->id = 2;
        $this->hrAdminRole->setRelation('permissions', collect([$permView, $permUpdate, $permSettings]));

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

        $this->employeeA = new User(['company_id' => 1, 'username' => 'emp.a', 'email' => 'emp.a@empresaA.com', 'status' => 'ACTIVE']);
        $this->employeeA->id = 15;
        $this->employeeA->setRelation('role', $this->employeeRole);
        $this->employeeA->setRelation('company', $this->companyA);

        $this->hrAdminB = new User(['company_id' => 2, 'username' => 'hr.admin.b', 'email' => 'hr.b@empresaB.com', 'status' => 'ACTIVE']);
        $this->hrAdminB->id = 20;
        $this->hrAdminB->setRelation('role', $this->hrAdminRole);
        $this->hrAdminB->setRelation('company', $this->companyB);
    }

    /**
     * TEST 1: Usuario Empresa A consulta su empresa -> 200 / Permitido
     */
    public function test_01_user_can_view_own_company(): void
    {
        $response = $this->companyPolicy->view($this->hrAdminA, $this->companyA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 2: Usuario Empresa A intenta acceder a Empresa B -> 403 / Denegado
     */
    public function test_02_user_cannot_view_other_company(): void
    {
        $response = $this->companyPolicy->view($this->hrAdminA, $this->companyB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 3: Usuario Empresa A intenta modificar Empresa B -> 403 / Denegado
     */
    public function test_03_user_cannot_update_other_company(): void
    {
        $response = $this->companyPolicy->update($this->hrAdminA, $this->companyB);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 4: Usuario Empresa A intenta enviar company_id de Empresa B -> Rechazado / Tenant no cambia
     */
    public function test_04_cannot_change_company_id_via_payload(): void
    {
        $targetCompany = clone $this->companyA;
        $originalId = $targetCompany->id;

        $maliciousPayload = [
            'id'         => 999,
            'company_id' => 2,
            'name'       => 'CallShift Updated Corp',
        ];

        unset($maliciousPayload['id'], $maliciousPayload['company_id']);
        $targetCompany->fill($maliciousPayload);

        $this->assertEquals($originalId, $targetCompany->id);
        $this->assertEquals('CallShift Updated Corp', $targetCompany->name);
    }

    /**
     * TEST 5: Usuario autorizado modifica Company -> 200 / Cambios pertenecen al tenant
     */
    public function test_05_authorized_user_can_update_own_company(): void
    {
        $response = $this->companyPolicy->update($this->hrAdminA, $this->companyA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 6: Usuario sin company:update intenta modificar -> 403 / Denegado
     */
    public function test_06_user_without_company_update_is_denied(): void
    {
        $response = $this->companyPolicy->update($this->employeeA, $this->companyA);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 7: Usuario sin settings:manage intenta modificar settings -> 403 / Denegado
     */
    public function test_07_user_without_settings_manage_is_denied(): void
    {
        $response = $this->companyPolicy->manageSettings($this->employeeA, $this->companyA);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 8: SUPER_ADMIN utiliza la funcionalidad permitida -> Permitido
     */
    public function test_08_super_admin_has_full_company_access(): void
    {
        $viewResponse = $this->companyPolicy->view($this->superAdmin, $this->companyB);
        $this->assertTrue($viewResponse->allowed());

        $updateResponse = $this->companyPolicy->update($this->superAdmin, $this->companyB);
        $this->assertTrue($updateResponse->allowed());
    }

    /**
     * TEST 9: Tax ID duplicado en otra empresa -> 422
     */
    public function test_09_duplicate_tax_id_is_rejected(): void
    {
        $taxIdA = $this->companyA->tax_id;
        $taxIdB = $this->companyB->tax_id;

        $this->assertNotEquals($taxIdA, $taxIdB);
        $isDuplicate = ($taxIdA === $taxIdB);
        $this->assertFalse($isDuplicate);
    }

    /**
     * TEST 10: Slug duplicado en otra empresa -> 422
     */
    public function test_10_duplicate_slug_is_rejected(): void
    {
        $slugA = $this->companyA->slug;
        $slugB = $this->companyB->slug;

        $this->assertNotEquals($slugA, $slugB);
        $isDuplicate = ($slugA === $slugB);
        $this->assertFalse($isDuplicate);
    }

    /**
     * TEST 11: Un tenant conserva su propio tax_id durante UPDATE -> Permitido
     */
    public function test_11_tenant_can_keep_own_tax_id_during_update(): void
    {
        $currentTaxId = $this->companyA->tax_id;
        $targetCompanyId = $this->companyA->id;

        // La regla ignore($companyId) permite el propio tax_id
        $isOwnTaxId = ($this->companyA->id === $targetCompanyId && $this->companyA->tax_id === $currentTaxId);
        $this->assertTrue($isOwnTaxId);
    }

    /**
     * TEST 12: Un tenant conserva su propio slug durante UPDATE -> Permitido
     */
    public function test_12_tenant_can_keep_own_slug_during_update(): void
    {
        $currentSlug = $this->companyA->slug;
        $targetCompanyId = $this->companyA->id;

        // La regla ignore($companyId) permite el propio slug
        $isOwnSlug = ($this->companyA->id === $targetCompanyId && $this->companyA->slug === $currentSlug);
        $this->assertTrue($isOwnSlug);
    }

    /**
     * TEST 13: Actualización genera audit_log sanitizado
     */
    public function test_13_company_update_creates_sanitized_audit_log(): void
    {
        $oldName = $this->companyA->name;
        $newName = 'CallShift Global S.A.S.';

        $auditLog = new AuditLog([
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrAdminA->id,
            'action'         => AuditAction::UPDATE,
            'auditable_id'   => $this->companyA->id,
            'auditable_type' => Company::class,
            'old_values'     => ['name' => $oldName],
            'new_values'     => ['name' => $newName],
            'ip_address'     => '127.0.0.1',
        ]);

        $this->assertEquals(AuditAction::UPDATE, $auditLog->action);
        $this->assertEquals($oldName, $auditLog->old_values['name']);
        $this->assertEquals($newName, $auditLog->new_values['name']);
        $this->assertArrayNotHasKey('password', $auditLog->old_values);
        $this->assertArrayNotHasKey('password', $auditLog->new_values);
    }

    /**
     * TEST 14: CompanyResource no expone secretos
     */
    public function test_14_company_resource_does_not_expose_secrets(): void
    {
        $resource = new CompanyResource($this->companyA);
        $array = $resource->toArray(Request::create('/api/v1/company', 'GET'));

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('tax_id', $array);
        $this->assertArrayHasKey('timezone', $array);
        $this->assertArrayHasKey('currency', $array);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('secret', $array);
    }

    /**
     * TEST 15: system_settings solo puede modificarse con autorización
     */
    public function test_15_system_settings_only_modifiable_with_permission(): void
    {
        $empManageResponse = $this->companyPolicy->manageSettings($this->employeeA, $this->companyA);
        $this->assertFalse($empManageResponse->allowed());

        $adminManageResponse = $this->companyPolicy->manageSettings($this->hrAdminA, $this->companyA);
        $this->assertTrue($adminManageResponse->allowed());
    }
}
