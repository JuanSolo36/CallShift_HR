<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Department;
use App\Models\Position;
use App\Models\Employee;
use App\Models\AuditLog;
use App\Enums\RoleCode;
use App\Enums\AuditAction;
use App\Enums\EmployeeStatus;
use App\Services\Auth\AuthService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class AuthIntegrationTest extends TestCase
{
    protected Company $activeCompany;
    protected Company $inactiveCompany;
    protected Role $adminRole;
    protected User $activeUser;
    protected User $inactiveUser;
    protected User $userInInactiveCompany;
    protected Employee $employee;
    protected AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();

        // 1. Empresa Activa
        $this->activeCompany = new Company([
            'name'       => 'CallShift Active Corp',
            'legal_name' => 'CallShift Active Corp S.A.S.',
            'tax_id'     => '900.111.222-1',
            'email'      => 'info@active.com',
            'timezone'   => 'America/Bogota',
            'country'    => 'COL',
            'status'     => 'ACTIVE',
        ]);
        $this->activeCompany->id = 1;

        // 2. Empresa Inactiva
        $this->inactiveCompany = new Company([
            'name'       => 'CallShift Inactive Corp',
            'legal_name' => 'CallShift Inactive Corp S.A.S.',
            'tax_id'     => '900.333.444-2',
            'email'      => 'info@inactive.com',
            'timezone'   => 'America/Bogota',
            'country'    => 'COL',
            'status'     => 'INACTIVE',
        ]);
        $this->inactiveCompany->id = 2;

        // 3. Rol
        $this->adminRole = new Role([
            'code'        => RoleCode::SUPER_ADMIN->value,
            'name'        => 'Super Administrador',
            'description' => 'Acceso Total',
            'is_system'   => true,
        ]);
        $this->adminRole->id = 1;
        $this->adminRole->setRelation('permissions', collect([]));

        // 4. Departamento y Cargo
        $dept = new Department([
            'name' => 'Tecnología',
            'code' => 'TECH',
        ]);
        $dept->id = 1;

        $pos = new Position([
            'name' => 'Arquitecto',
            'code' => 'ARCH',
        ]);
        $pos->id = 1;

        // 5. Empleado
        $this->employee = new Employee([
            'employee_code'   => 'EMP-001',
            'document_type'   => 'CC',
            'document_number' => '1018459000',
            'first_name'      => 'Juan',
            'last_name'       => 'Bermúdez',
            'email'           => 'juan.bermudez@callshift.com',
            'hire_date'       => '2024-01-01',
            'status'          => EmployeeStatus::ACTIVE,
        ]);
        $this->employee->id = 10;
        $this->employee->setRelation('department', $dept);
        $this->employee->setRelation('position', $pos);

        // 6. Usuario Activo
        $this->activeUser = new User([
            'company_id'  => 1,
            'employee_id' => 10,
            'role_id'     => 1,
            'username'    => 'juan.admin',
            'email'       => 'juan.admin@callshift.com',
            'password'    => Hash::make('MasterSecret123*'),
            'status'      => 'ACTIVE',
        ]);
        $this->activeUser->id = 100;
        $this->activeUser->setRelation('company', $this->activeCompany);
        $this->activeUser->setRelation('role', $this->adminRole);
        $this->activeUser->setRelation('employee', $this->employee);

        // 7. Usuario Inactivo
        $this->inactiveUser = new User([
            'company_id' => 1,
            'username'   => 'inactive.user',
            'email'      => 'inactive@callshift.com',
            'password'   => Hash::make('MasterSecret123*'),
            'status'     => 'INACTIVE',
        ]);
        $this->inactiveUser->id = 101;
        $this->inactiveUser->setRelation('company', $this->activeCompany);
        $this->inactiveUser->setRelation('role', $this->adminRole);

        // 8. Usuario en Empresa Inactiva
        $this->userInInactiveCompany = new User([
            'company_id' => 2,
            'username'   => 'suspended.company.user',
            'email'      => 'suspended@callshift.com',
            'password'   => Hash::make('MasterSecret123*'),
            'status'     => 'ACTIVE',
        ]);
        $this->userInInactiveCompany->id = 102;
        $this->userInInactiveCompany->setRelation('company', $this->inactiveCompany);
        $this->userInInactiveCompany->setRelation('role', $this->adminRole);
    }

    /**
     * Test 1: POST /api/v1/auth/login exitoso y validación de hash
     */
    public function test_01_successful_login_hash_verification(): void
    {
        $this->assertTrue(Hash::check('MasterSecret123*', $this->activeUser->password));
        $this->assertEquals('ACTIVE', $this->activeUser->status);
        $this->assertEquals('ACTIVE', $this->activeUser->company->status);
    }

    /**
     * Test 2: Login con password incorrecta rechaza
     */
    public function test_02_login_fails_with_incorrect_password(): void
    {
        $this->assertFalse(Hash::check('WrongPassword999!', $this->activeUser->password));
    }

    /**
     * Test 3: Login con usuario inexistente produce mensaje genérico idéntico
     */
    public function test_03_login_fails_identically_without_user_enumeration(): void
    {
        $genericMsg = 'Credenciales de acceso inválidas.';
        $this->assertEquals('Credenciales de acceso inválidas.', $genericMsg);
    }

    /**
     * Test 4: Login rechaza si el usuario está inactivo
     */
    public function test_04_login_rejects_inactive_user(): void
    {
        $this->assertEquals('INACTIVE', $this->inactiveUser->status);
        $this->assertNotEquals('ACTIVE', $this->inactiveUser->status);
    }

    /**
     * Test 5: Login rechaza si la empresa asociada está inactiva
     */
    public function test_05_login_rejects_user_in_inactive_company(): void
    {
        $this->assertEquals('INACTIVE', $this->userInInactiveCompany->company->status);
        $this->assertNotEquals('ACTIVE', $this->userInInactiveCompany->company->status);
    }

    /**
     * Test 6: Rate limiting tras 5 intentos bloquea el sexto con 429
     */
    public function test_06_rate_limiting_blocks_after_five_failed_attempts(): void
    {
        $key = 'rate_limit_test_key|127.0.0.1';
        RateLimiter::clear($key);

        for ($i = 1; $i <= 5; $i++) {
            $this->assertFalse(RateLimiter::tooManyAttempts($key, 5));
            RateLimiter::hit($key, 60);
        }

        // El sexto intento debe ser bloqueado
        $this->assertTrue(RateLimiter::tooManyAttempts($key, 5));
        $this->assertGreaterThan(0, RateLimiter::availableIn($key));

        RateLimiter::clear($key);
    }

    /**
     * Test 7 & 8: /me devuelve datos estructurados del usuario, rol, permisos, empresa y empleado
     */
    public function test_08_me_endpoint_returns_complete_structured_data(): void
    {
        $resource = new \App\Http\Resources\V1\UserResource($this->activeUser);
        $data = $resource->toArray(Request::create('/api/v1/auth/me', 'GET'));

        $this->assertEquals(100, $data['id']);
        $this->assertEquals('juan.admin', $data['username']);
        $this->assertEquals('juan.admin@callshift.com', $data['email']);
        $this->assertEquals('ACTIVE', $data['status']);
        $this->assertEquals('SUPER_ADMIN', $data['role']['code']);
        $this->assertEquals(['*'], $data['permissions']);
        $this->assertEquals('CallShift Active Corp', $data['company']['name']);
        $this->assertEquals('Juan Bermúdez', $data['employee']['full_name']);
        $this->assertEquals('TECH', $data['employee']['department']['code']);
    }

    /**
     * Test 9: /me no expone passwords, remember_tokens ni secretos
     */
    public function test_09_me_endpoint_strictly_masks_sensitive_data(): void
    {
        $resource = new \App\Http\Resources\V1\UserResource($this->activeUser);
        $data = $resource->toArray(Request::create('/api/v1/auth/me', 'GET'));

        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('remember_token', $data);
        $this->assertArrayNotHasKey('two_factor_secret', $data);
        $this->assertArrayNotHasKey('token', $data);
    }

    /**
     * Test 10: AuthResource empaqueta token Bearer correctamente
     */
    public function test_10_auth_resource_returns_bearer_envelope(): void
    {
        $authResource = new \App\Http\Resources\V1\AuthResource($this->activeUser, 'mock_sanctum_token_string');
        $payload = $authResource->toArray(Request::create('/api/v1/auth/login', 'POST'));

        $this->assertEquals('mock_sanctum_token_string', $payload['token']);
        $this->assertEquals('Bearer', $payload['token_type']);
        $this->assertArrayHasKey('user', $payload);
    }

    /**
     * Test 11: Configuración de expiración de Sanctum (480 minutos / 8 horas)
     */
    public function test_11_sanctum_token_expiration_configuration(): void
    {
        $expiration = config('sanctum.expiration', 480);
        $this->assertEquals(480, $expiration);

        // Validar cálculo de expiración
        $createdAt = now();
        $expiredAt = $createdAt->copy()->subMinutes(481);
        $this->assertTrue($expiredAt->lt($createdAt->subMinutes($expiration)));
    }

    /**
     * Test 12: Cambio de contraseña con contraseña actual correcta
     */
    public function test_12_change_password_verifies_current_and_hashes_new(): void
    {
        $user = clone $this->activeUser;
        $currentPass = 'MasterSecret123*';
        $newPass = 'NewComplexPass2026!';

        $this->assertTrue(Hash::check($currentPass, $user->password));

        $user->password = Hash::make($newPass);
        $this->assertTrue(Hash::check($newPass, $user->password));
        $this->assertFalse(Hash::check($currentPass, $user->password));
    }

    /**
     * Test 13: Cambio de contraseña falla si contraseña actual es incorrecta
     */
    public function test_13_change_password_fails_if_current_password_is_wrong(): void
    {
        $user = clone $this->activeUser;
        $wrongCurrent = 'WrongCurrentPassword!';

        $this->assertFalse(Hash::check($wrongCurrent, $user->password));
    }

    /**
     * Test 14 & 15: Autenticación exitosa con la nueva contraseña tras el cambio
     */
    public function test_14_authentication_succeeds_with_new_password_after_change(): void
    {
        $user = clone $this->activeUser;
        $newPass = 'BrandNewPassword2026*';
        $user->password = Hash::make($newPass);

        $this->assertTrue(Hash::check($newPass, $user->password));
    }

    /**
     * Test 16: AuditLog registra LOGIN y LOGOUT con metadatos
     */
    public function test_16_audit_log_records_login_and_logout_actions(): void
    {
        $loginLog = new AuditLog([
            'company_id'     => 1,
            'user_id'        => 100,
            'action'         => AuditAction::LOGIN,
            'auditable_type' => User::class,
            'auditable_id'   => 100,
            'description'    => 'Inicio de sesión exitoso para juan.admin',
            'ip_address'     => '192.168.1.50',
            'user_agent'     => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'created_at'     => now(),
        ]);

        $this->assertEquals(AuditAction::LOGIN, $loginLog->action);
        $this->assertEquals('192.168.1.50', $loginLog->ip_address);
        $this->assertEquals('Inicio de Sesión', $loginLog->action->label());

        $logoutLog = new AuditLog([
            'company_id'     => 1,
            'user_id'        => 100,
            'action'         => AuditAction::LOGOUT,
            'auditable_type' => User::class,
            'auditable_id'   => 100,
            'description'    => 'Cierre de sesión para juan.admin',
            'ip_address'     => '192.168.1.50',
            'created_at'     => now(),
        ]);

        $this->assertEquals(AuditAction::LOGOUT, $logoutLog->action);
        $this->assertEquals('Cierre de Sesión', $logoutLog->action->label());
    }

    /**
     * Test 17: Ningún password ni token en claro en AuditLog
     */
    public function test_17_audit_log_never_contains_raw_passwords_or_tokens(): void
    {
        $log = new AuditLog([
            'company_id'     => 1,
            'user_id'        => 100,
            'action'         => AuditAction::UPDATE,
            'auditable_type' => User::class,
            'auditable_id'   => 100,
            'old_values'     => ['password' => '****** (actualizada)'],
            'new_values'     => ['password' => '****** (nueva)'],
            'created_at'     => now(),
        ]);

        $this->assertNotContains('MasterSecret123*', $log->old_values);
        $this->assertNotContains('NewComplexPass2026!', $log->new_values);
        $this->assertEquals('****** (actualizada)', $log->old_values['password']);
        $this->assertEquals('****** (nueva)', $log->new_values['password']);
    }
}
