<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Role;
use App\Models\Employee;
use App\Enums\RoleCode;
use App\Http\Resources\V1\UserResource;
use App\Http\Resources\V1\AuthResource;
use Illuminate\Http\Request;

class AuthServiceUnitTest extends TestCase
{
    public function test_user_resource_transforms_cleanly_without_sensitive_data(): void
    {
        $company = new Company([
            'name'     => 'CallShift Enterprise',
            'timezone' => 'America/Bogota',
            'country'  => 'COL',
        ]);
        $company->id = 1;

        $role = new Role([
            'code' => RoleCode::HR_ADMIN->value,
            'name' => 'Administrador de RRHH',
        ]);
        $role->id = 2;
        $role->setRelation('permissions', collect([]));

        $employee = new Employee([
            'employee_code' => 'EMP-001',
            'first_name'    => 'Carlos',
            'last_name'     => 'Mendoza',
            'email'         => 'carlos.mendoza@callshift.com',
        ]);
        $employee->id = 10;

        $user = new User([
            'username'   => 'carlos.mendoza',
            'email'      => 'carlos.mendoza@callshift.com',
            'status'     => 'ACTIVE',
            'company_id' => 1,
        ]);
        $user->id = 5;
        $user->setRelation('company', $company);
        $user->setRelation('role', $role);
        $user->setRelation('employee', $employee);

        $resource = new UserResource($user);
        $array = $resource->toArray(Request::create('/api/v1/auth/me', 'GET'));

        // Verificaciones
        $this->assertEquals(5, $array['id']);
        $this->assertEquals('carlos.mendoza', $array['username']);
        $this->assertEquals('carlos.mendoza@callshift.com', $array['email']);
        $this->assertEquals('HR_ADMIN', $array['role']['code']);
        $this->assertEquals('CallShift Enterprise', $array['company']['name']);
        $this->assertEquals('Carlos Mendoza', $array['employee']['full_name']);

        // Asegurar que NO existan campos sensibles
        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
        $this->assertArrayNotHasKey('two_factor_secret', $array);
    }

    public function test_auth_resource_wraps_bearer_token(): void
    {
        $user = new User([
            'username' => 'admin',
            'email'    => 'admin@callshift.com',
            'status'   => 'ACTIVE',
        ]);
        $user->id = 1;
        $user->setRelation('role', null);
        $user->setRelation('employee', null);
        $user->setRelation('company', null);

        $authResource = new AuthResource($user, 'sample_plain_text_sanctum_token');
        $array = $authResource->toArray(Request::create('/api/v1/auth/login', 'POST'));

        $this->assertEquals('sample_plain_text_sanctum_token', $array['token']);
        $this->assertEquals('Bearer', $array['token_type']);
        $this->assertArrayHasKey('user', $array);
    }
}
