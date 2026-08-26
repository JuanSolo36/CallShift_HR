<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Models\Employee;
use App\Models\User;
use App\Models\Role;
use App\Enums\RoleCode;
use App\Enums\EmployeeStatus;

class MultiTenancyTest extends TestCase
{
    public function test_employee_model_casts_and_properties(): void
    {
        $employee = new Employee([
            'first_name' => 'Carlos',
            'last_name'  => 'Mendoza',
            'status'     => EmployeeStatus::ACTIVE,
        ]);

        $this->assertEquals('Carlos Mendoza', $employee->full_name);
        $this->assertEquals(EmployeeStatus::ACTIVE, $employee->status);
        $this->assertTrue($employee->status->isSchedulable());
    }

    public function test_user_role_and_permission_resolution(): void
    {
        $role = new Role([
            'code' => RoleCode::SUPER_ADMIN->value,
            'name' => 'Super Administrador',
        ]);

        $user = new User([
            'username'   => 'admin',
            'email'      => 'admin@callshift.com',
            'company_id' => 1,
        ]);
        $user->setRelation('role', $role);

        $this->assertTrue($user->hasRole('SUPER_ADMIN'));
        $this->assertTrue($user->hasPermission('any:permission:superadmin:bypass'));
    }
}
