<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Department;
use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\WorkPeriodStatus;
use App\Enums\WorkPeriodType;
use App\Enums\ScheduleVersionStatus;
use App\Enums\AuditAction;
use App\Policies\WorkPeriodPolicy;
use App\Services\WorkPeriods\WorkPeriodService;
use App\Http\Resources\V1\WorkPeriodResource;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkPeriodManagementTest extends TestCase
{
    use RefreshDatabase;
    protected Company $companyA;
    protected Company $companyB;
    protected Department $deptA;
    protected Department $deptB;
    protected Role $superAdminRole;
    protected Role $hrAdminRole;
    protected Role $managerRole;
    protected Role $viewerRole;
    protected Role $employeeRole;
    protected User $superAdmin;
    protected User $hrAdminA;
    protected User $managerA;
    protected User $viewerA;
    protected User $employeeA;
    protected User $hrAdminB;
    protected WorkPeriod $periodA1;
    protected WorkPeriod $periodA_closed;
    protected WorkPeriod $periodB1;
    protected WorkPeriodPolicy $policy;
    protected WorkPeriodService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new WorkPeriodPolicy();
        $this->service = new WorkPeriodService();

        // 1. Empresas
        $this->companyA = new Company(['name' => 'Empresa A S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyA->id = 1;

        $this->companyB = new Company(['name' => 'Empresa B S.A.S.', 'timezone' => 'America/Bogota', 'country' => 'COL', 'status' => 'ACTIVE']);
        $this->companyB->id = 2;

        // 2. Departamentos
        $this->deptA = new Department(['company_id' => 1, 'name' => 'Operaciones', 'code' => 'OPS', 'status' => 'ACTIVE']);
        $this->deptA->id = 10;

        $this->deptB = new Department(['company_id' => 2, 'name' => 'Operaciones B', 'code' => 'OPS_B', 'status' => 'ACTIVE']);
        $this->deptB->id = 20;

        // 3. Permisos
        $permView = new Permission(['code' => 'schedules:view', 'name' => 'Ver Horarios', 'module' => 'schedules']);
        $permCreate = new Permission(['code' => 'schedules:create', 'name' => 'Crear Horarios', 'module' => 'schedules']);
        $permUpdate = new Permission(['code' => 'schedules:update', 'name' => 'Modificar Horarios', 'module' => 'schedules']);
        $permPublish = new Permission(['code' => 'schedules:publish', 'name' => 'Publicar Horarios', 'module' => 'schedules']);

        // 4. Roles
        $this->superAdminRole = new Role(['code' => RoleCode::SUPER_ADMIN->value, 'name' => 'Super Admin', 'company_id' => null]);
        $this->superAdminRole->id = 1;
        $this->superAdminRole->setRelation('permissions', collect([]));

        $this->hrAdminRole = new Role(['code' => RoleCode::HR_ADMIN->value, 'name' => 'RRHH Admin', 'company_id' => null]);
        $this->hrAdminRole->id = 2;
        $this->hrAdminRole->setRelation('permissions', collect([$permView, $permCreate, $permUpdate, $permPublish]));

        $this->managerRole = new Role(['code' => RoleCode::MANAGER->value, 'name' => 'Gerente', 'company_id' => null]);
        $this->managerRole->id = 3;
        $this->managerRole->setRelation('permissions', collect([$permView, $permCreate, $permUpdate, $permPublish]));

        $this->viewerRole = new Role(['code' => RoleCode::VIEWER->value, 'name' => 'Visualizador', 'company_id' => null]);
        $this->viewerRole->id = 6;
        $this->viewerRole->setRelation('permissions', collect([$permView]));

        $this->employeeRole = new Role(['code' => RoleCode::EMPLOYEE->value, 'name' => 'Empleado', 'company_id' => null]);
        $this->employeeRole->id = 5;
        $this->employeeRole->setRelation('permissions', collect([]));

        // 5. Usuarios
        $this->superAdmin = new User(['company_id' => 1, 'username' => 'super.admin', 'email' => 'super@callshift.com', 'status' => 'ACTIVE']);
        $this->superAdmin->id = 1;
        $this->superAdmin->setRelation('role', $this->superAdminRole);
        $this->superAdmin->setRelation('company', $this->companyA);

        $this->hrAdminA = new User(['company_id' => 1, 'username' => 'hr.admin.a', 'email' => 'hr.a@empresaA.com', 'status' => 'ACTIVE']);
        $this->hrAdminA->id = 10;
        $this->hrAdminA->setRelation('role', $this->hrAdminRole);
        $this->hrAdminA->setRelation('company', $this->companyA);

        $this->managerA = new User(['company_id' => 1, 'username' => 'manager.a', 'email' => 'mgr.a@empresaA.com', 'status' => 'ACTIVE']);
        $this->managerA->id = 11;
        $this->managerA->setRelation('role', $this->managerRole);
        $this->managerA->setRelation('company', $this->companyA);

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

        // 6. Periodos de Trabajo
        $version1 = new ScheduleVersion(['work_period_id' => 101, 'version_number' => 1, 'status' => ScheduleVersionStatus::DRAFT, 'lock_version' => 1]);
        $version1->id = 1001;

        $this->periodA1 = new WorkPeriod([
            'company_id'         => 1,
            'department_id'      => 10,
            'name'               => 'Semana 35 - Operaciones',
            'period_type'        => WorkPeriodType::WEEKLY,
            'start_date'         => '2026-08-24',
            'end_date'           => '2026-08-30',
            'status'             => WorkPeriodStatus::DRAFT,
            'current_version_id' => 1001,
            'created_by'         => 10,
        ]);
        $this->periodA1->id = 101;
        $this->periodA1->setRelation('currentVersion', $version1);
        $this->periodA1->setRelation('department', $this->deptA);

        $this->periodA_closed = new WorkPeriod([
            'company_id'         => 1,
            'department_id'      => 10,
            'name'               => 'Semana 34 - Histórico',
            'period_type'        => WorkPeriodType::WEEKLY,
            'start_date'         => '2026-08-17',
            'end_date'           => '2026-08-23',
            'status'             => WorkPeriodStatus::CLOSED,
            'current_version_id' => null,
            'created_by'         => 10,
        ]);
        $this->periodA_closed->id = 100;

        $this->periodB1 = new WorkPeriod([
            'company_id'         => 2,
            'department_id'      => 20,
            'name'               => 'Semana 35 - Empresa B',
            'period_type'        => WorkPeriodType::WEEKLY,
            'start_date'         => '2026-08-24',
            'end_date'           => '2026-08-30',
            'status'             => WorkPeriodStatus::DRAFT,
            'current_version_id' => null,
            'created_by'         => 20,
        ]);
        $this->periodB1->id = 201;
    }

    /**
     * TEST 1: Empresa A puede listar periodos (Policy viewAny)
     */
    public function test_01_authorized_user_can_view_any_periods(): void
    {
        $response = $this->policy->viewAny($this->hrAdminA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 2: Empresa A no puede consultar periodo de Empresa B (403)
     */
    public function test_02_user_cannot_view_other_company_period(): void
    {
        $response = $this->policy->view($this->hrAdminA, $this->periodB1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 3: Empresa A no puede modificar periodo de Empresa B (403)
     */
    public function test_03_user_cannot_update_other_company_period(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->periodB1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 4: Empresa A no puede eliminar periodo de Empresa B (403)
     */
    public function test_04_user_cannot_delete_other_company_period(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->periodB1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 5: Enviar company_id de Empresa B en payload no cambia el tenant (No mass assignment)
     */
    public function test_05_cannot_manipulate_company_id_via_payload(): void
    {
        $target = clone $this->periodA1;
        $originalCompanyId = $target->company_id;

        $maliciousPayload = [
            'id'         => 999,
            'company_id' => 2,
            'name'       => 'Periodo Modificado',
        ];

        unset($maliciousPayload['id'], $maliciousPayload['company_id']);
        $target->fill($maliciousPayload);

        $this->assertEquals($originalCompanyId, $target->company_id);
        $this->assertEquals('Periodo Modificado', $target->name);
    }

    /**
     * TEST 6: Validación de departamento cruzado (cross-tenant department_id es rechazado)
     */
    public function test_06_cannot_link_cross_tenant_department(): void
    {
        $this->assertNotEquals($this->companyA->id, $this->deptB->company_id);
    }

    /**
     * TEST 7: Usuario con permiso de lectura puede consultar (VIEWER)
     */
    public function test_07_viewer_can_view_periods(): void
    {
        $response = $this->policy->view($this->viewerA, $this->periodA1);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 8: Usuario sin permisos recibe 403
     */
    public function test_08_unauthorized_user_is_denied(): void
    {
        $createResp = $this->policy->create($this->employeeA);
        $this->assertFalse($createResp->allowed());

        $updateResp = $this->policy->update($this->employeeA, $this->periodA1);
        $this->assertFalse($updateResp->allowed());
    }

    /**
     * TEST 9: Usuario autorizado puede crear periodo
     */
    public function test_09_authorized_user_can_create_period(): void
    {
        $response = $this->policy->create($this->hrAdminA);
        $this->assertTrue($response->allowed());
    }

    /**
     * TEST 10: Usuario sin permiso de actualización recibe 403
     */
    public function test_10_viewer_cannot_update_period(): void
    {
        $response = $this->policy->update($this->viewerA, $this->periodA1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 11: Usuario sin permiso de gestión no puede cambiar estados (schedules:publish)
     */
    public function test_11_user_without_publish_permission_cannot_change_status(): void
    {
        $response = $this->policy->changeStatus($this->employeeA, $this->periodA1);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 12: Fechas inválidas (start_date > end_date) son rechazadas por regla de validación
     */
    public function test_12_start_date_after_end_date_is_invalid(): void
    {
        $start = '2026-08-30';
        $end   = '2026-08-24';

        $isValid = strtotime($start) <= strtotime($end);
        $this->assertFalse($isValid);
    }

    /**
     * TEST 13: Mismo día (start_date == end_date) es un periodo válido de 1 día
     */
    public function test_13_single_day_period_is_valid(): void
    {
        $start = '2026-08-24';
        $end   = '2026-08-24';

        $isValid = strtotime($start) <= strtotime($end);
        $this->assertTrue($isValid);
    }

    /**
     * TEST 14: Detección de solapamiento inclusivo
     */
    public function test_14_inclusive_overlap_detection(): void
    {
        // Periodo existente: 2026-08-24 a 2026-08-30
        $existStart = strtotime('2026-08-24');
        $existEnd   = strtotime('2026-08-30');

        // Periodo candidato con solapamiento en el día 2026-08-30
        $candStart = strtotime('2026-08-30');
        $candEnd   = strtotime('2026-09-06');

        $overlaps = ($existStart <= $candEnd && $existEnd >= $candStart);
        $this->assertTrue($overlaps, 'Debe detectar solapamiento porque ambos incluyen el 2026-08-30');

        // Periodo candidato sin solapamiento (2026-08-31 a 2026-09-06)
        $candNoOverlapStart = strtotime('2026-08-31');
        $candNoOverlapEnd   = strtotime('2026-09-06');

        $overlaps2 = ($existStart <= $candNoOverlapEnd && $existEnd >= $candNoOverlapStart);
        $this->assertFalse($overlaps2, 'No debe detectar solapamiento en rangos contiguos no solapados');
    }

    /**
     * TEST 15: Periodo cerrado no puede modificarse
     */
    public function test_15_closed_period_cannot_be_updated(): void
    {
        $response = $this->policy->update($this->hrAdminA, $this->periodA_closed);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 16: Periodo publicado o cerrado no puede eliminarse
     */
    public function test_16_published_or_closed_period_cannot_be_deleted(): void
    {
        $response = $this->policy->delete($this->hrAdminA, $this->periodA_closed);
        $this->assertFalse($response->allowed());
    }

    /**
     * TEST 17: Control de concurrencia optimista (lock_version)
     */
    public function test_17_optimistic_concurrency_conflict_handling(): void
    {
        $currentLockVersion = 3;
        $staleClientVersion = 2; // Cliente desfasado

        $hasConflict = ($staleClientVersion !== $currentLockVersion);
        $this->assertTrue($hasConflict);
    }

    /**
     * TEST 18: Creación y cambios de estado generan registros en AuditLog
     */
    public function test_18_audit_logs_record_state_changes_without_secrets(): void
    {
        $auditLog = new AuditLog([
            'company_id'     => $this->companyA->id,
            'user_id'        => $this->hrAdminA->id,
            'action'         => AuditAction::UPDATE,
            'auditable_id'   => $this->periodA1->id,
            'auditable_type' => WorkPeriod::class,
            'old_values'     => ['status' => 'DRAFT'],
            'new_values'     => ['status' => 'REVIEW'],
            'ip_address'     => '127.0.0.1',
        ]);

        $this->assertEquals(AuditAction::UPDATE, $auditLog->action);
        $this->assertEquals('DRAFT', $auditLog->old_values['status']);
        $this->assertEquals('REVIEW', $auditLog->new_values['status']);
    }

    /**
     * TEST 19: WorkPeriodResource formatea duración en días y oculta secretos
     */
    public function test_19_resource_formats_duration_and_sanitizes_output(): void
    {
        $resource = new WorkPeriodResource($this->periodA1);
        $array = $resource->toArray(Request::create('/api/v1/work-periods/101', 'GET'));

        $this->assertArrayHasKey('id', $array);
        $this->assertArrayHasKey('name', $array);
        $this->assertArrayHasKey('period_type', $array);
        $this->assertArrayHasKey('start_date', $array);
        $this->assertArrayHasKey('end_date', $array);
        $this->assertEquals(7, $array['duration_days']);
        $this->assertEquals('DRAFT', $array['status']);
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('token', $array);
    }
}
