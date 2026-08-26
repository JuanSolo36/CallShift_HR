<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Enums\RoleCode;

class CompanyDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Empresa Principal
        $companyId = DB::table('companies')->insertGetId([
            'name'       => 'CallShift Enterprise S.A.S.',
            'legal_name' => 'CallShift Human Resources & Workforce Technologies S.A.S.',
            'tax_id'     => '901.845.120-4',
            'email'      => 'contacto@callshift.com',
            'phone'      => '+57 (601) 745-9000',
            'address'    => 'Av. El Dorado #68C-61, Torre Empresarial, Piso 10',
            'country'    => 'COL',
            'timezone'   => 'America/Bogota',
            'status'     => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Reglas de Negocio de la Empresa
        DB::table('business_rules')->insert([
            'company_id'                   => $companyId,
            'department_id'                => null, // Regla corporativa global
            'max_daily_hours'              => 10.0,
            'min_daily_hours'              => 4.0,
            'max_weekly_hours'             => 48.0,
            'min_weekly_hours'             => 24.0,
            'min_rest_hours_between_shifts'=> 12.0,
            'max_consecutive_work_days'    => 6,
            'allow_night_shifts'           => true,
            'weekend_rotation_policy'      => 'FAIR_SHARE',
            'created_at'                   => now(),
            'updated_at'                   => now(),
        ]);

        // 3. Departamentos
        $deptData = [
            ['name' => 'Recursos Humanos', 'code' => 'HR', 'desc' => 'Gestión de talento, nómina y bienestar organizacional'],
            ['name' => 'Tecnología e Innovación', 'code' => 'TECH', 'desc' => 'Desarrollo de software, infraestructura y ciberseguridad'],
            ['name' => 'Operaciones y Servicios', 'code' => 'OPS', 'desc' => 'Supervisión operativa y control de jornadas'],
            ['name' => 'Atención al Cliente & Contact Center', 'code' => 'CC', 'desc' => 'Canales de atención multicanal y soporte telefónico'],
            ['name' => 'Finanzas y Administración', 'code' => 'FIN', 'desc' => 'Contabilidad, tesorería y compras'],
        ];

        $deptIds = [];
        foreach ($deptData as $d) {
            $deptIds[$d['code']] = DB::table('departments')->insertGetId([
                'company_id'  => $companyId,
                'name'        => $d['name'],
                'code'        => $d['code'],
                'description' => $d['desc'],
                'status'      => 'ACTIVE',
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }

        // 4. Cargos (Positions)
        $positionsData = [
            ['name' => 'Gerente de Recursos Humanos', 'code' => 'HR_MGR', 'dept' => 'HR'],
            ['name' => 'Analista de Talento Humano', 'code' => 'HR_ANA', 'dept' => 'HR'],
            ['name' => 'Arquitecto de Software', 'code' => 'TECH_ARCH', 'dept' => 'TECH'],
            ['name' => 'Desarrollador Full Stack', 'code' => 'TECH_DEV', 'dept' => 'TECH'],
            ['name' => 'Supervisor de Operaciones', 'code' => 'OPS_SUP', 'dept' => 'OPS'],
            ['name' => 'Coordinador de Contact Center', 'code' => 'CC_COORD', 'dept' => 'CC'],
            ['name' => 'Asesor de Servicio al Cliente', 'code' => 'CC_ADV', 'dept' => 'CC'],
            ['name' => 'Especialista en Soporte Técnico', 'code' => 'TECH_SUP', 'dept' => 'TECH'],
        ];

        $positionIds = [];
        foreach ($positionsData as $pos) {
            $positionIds[$pos['code']] = DB::table('positions')->insertGetId([
                'company_id'    => $companyId,
                'department_id' => $deptIds[$pos['dept']],
                'name'          => $pos['name'],
                'code'          => $pos['code'],
                'description'   => 'Descripción de responsabilidades para ' . $pos['name'],
                'status'        => 'ACTIVE',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // 5. Tipos de Contrato
        $empTypesData = [
            ['name' => 'Tiempo Completo Ordinario', 'code' => 'FULL_TIME_48', 'hours' => 48.0],
            ['name' => 'Tiempo Completo Reducido', 'code' => 'FULL_TIME_42', 'hours' => 42.0],
            ['name' => 'Medio Tiempo', 'code' => 'PART_TIME_24', 'hours' => 24.0],
            ['name' => 'Contrato de Aprendizaje / Prácticas', 'code' => 'INTERNSHIP', 'hours' => 40.0],
        ];

        $empTypeIds = [];
        foreach ($empTypesData as $et) {
            $empTypeIds[$et['code']] = DB::table('employment_types')->insertGetId([
                'company_id'           => $companyId,
                'name'                 => $et['name'],
                'code'                 => $et['code'],
                'default_weekly_hours' => $et['hours'],
                'status'               => 'ACTIVE',
                'created_at'           => now(),
                'updated_at'           => now(),
            ]);
        }

        // 6. Tipos de Turno
        $shiftsData = [
            [
                'name'             => 'Mañana Estándar (06:00 - 14:00)',
                'code'             => 'M06_14',
                'color_hex'        => '#3B82F6', // Azul
                'start_time'       => '06:00:00',
                'end_time'         => '14:00:00',
                'break_minutes'    => 60,
                'total_work_hours' => 7.00,
                'crosses_midnight' => false,
            ],
            [
                'name'             => 'Tarde Estándar (14:00 - 22:00)',
                'code'             => 'T14_22',
                'color_hex'        => '#F59E0B', // Ámbar
                'start_time'       => '14:00:00',
                'end_time'         => '22:00:00',
                'break_minutes'    => 60,
                'total_work_hours' => 7.00,
                'crosses_midnight' => false,
            ],
            [
                'name'             => 'Nocturno (22:00 - 06:00)',
                'code'             => 'N22_06',
                'color_hex'        => '#6366F1', // Índigo
                'start_time'       => '22:00:00',
                'end_time'         => '06:00:00',
                'break_minutes'    => 60,
                'total_work_hours' => 7.00,
                'crosses_midnight' => true, // CRUZA MEDIANOCHE
            ],
            [
                'name'             => 'Administrativo (08:00 - 17:00)',
                'code'             => 'ADM08_17',
                'color_hex'        => '#10B981', // Verde
                'start_time'       => '08:00:00',
                'end_time'         => '17:00:00',
                'break_minutes'    => 60,
                'total_work_hours' => 8.00,
                'crosses_midnight' => false,
            ],
        ];

        foreach ($shiftsData as $s) {
            DB::table('shift_types')->insert([
                'company_id'             => $companyId,
                'name'                   => $s['name'],
                'code'                   => $s['code'],
                'color_hex'              => $s['color_hex'],
                'start_time'             => $s['start_time'],
                'end_time'               => $s['end_time'],
                'break_duration_minutes' => $s['break_minutes'],
                'total_work_hours'       => $s['total_work_hours'],
                'crosses_midnight'       => $s['crosses_midnight'],
                'status'                 => 'ACTIVE',
                'created_at'             => now(),
                'updated_at'             => now(),
            ]);
        }

        // Obtener IDs de roles
        $roles = DB::table('roles')->pluck('id', 'code');

        // 7. Empleados de Demostración
        $employeesData = [
            [
                'code'       => 'EMP-001',
                'doc_num'    => '1018459101',
                'first_name' => 'Carlos',
                'last_name'  => 'Mendoza',
                'email'      => 'carlos.mendoza@callshift.com',
                'hire_date'  => '2024-01-15',
                'dept'       => 'HR',
                'pos'        => 'HR_MGR',
                'type'       => 'FULL_TIME_48',
                'role'       => 'HR_ADMIN',
            ],
            [
                'code'       => 'EMP-002',
                'doc_num'    => '1018459102',
                'first_name' => 'Laura',
                'last_name'  => 'Gómez',
                'email'      => 'laura.gomez@callshift.com',
                'hire_date'  => '2024-03-01',
                'dept'       => 'TECH',
                'pos'        => 'TECH_DEV',
                'type'       => 'FULL_TIME_48',
                'role'       => 'MANAGER',
            ],
            [
                'code'       => 'EMP-003',
                'doc_num'    => '1018459103',
                'first_name' => 'Andrés',
                'last_name'  => 'Restrepo',
                'email'      => 'andres.restrepo@callshift.com',
                'hire_date'  => '2024-05-10',
                'dept'       => 'OPS',
                'pos'        => 'OPS_SUP',
                'type'       => 'FULL_TIME_48',
                'role'       => 'SUPERVISOR',
            ],
            [
                'code'       => 'EMP-004',
                'doc_num'    => '1018459104',
                'first_name' => 'Valentina',
                'last_name'  => 'Torres',
                'email'      => 'valentina.torres@callshift.com',
                'hire_date'  => '2025-01-10',
                'dept'       => 'CC',
                'pos'        => 'CC_ADV',
                'type'       => 'FULL_TIME_48',
                'role'       => 'EMPLOYEE',
            ],
            [
                'code'       => 'EMP-005',
                'doc_num'    => '1018459105',
                'first_name' => 'Mateo',
                'last_name'  => 'Ríos',
                'email'      => 'mateo.rios@callshift.com',
                'hire_date'  => '2025-02-01',
                'dept'       => 'CC',
                'pos'        => 'CC_ADV',
                'type'       => 'FULL_TIME_48',
                'role'       => 'EMPLOYEE',
            ],
        ];

        $adminUserId = DB::table('users')->insertGetId([
            'company_id'    => $companyId,
            'employee_id'   => null, // Super admin corporativo global
            'role_id'       => $roles['SUPER_ADMIN'],
            'username'      => 'admin',
            'email'         => 'admin@callshift.com',
            'password'      => Hash::make('Admin123*'),
            'status'        => 'ACTIVE',
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        foreach ($employeesData as $emp) {
            $empId = DB::table('employees')->insertGetId([
                'company_id'         => $companyId,
                'employee_code'      => $emp['code'],
                'document_type'      => 'CC',
                'document_number'    => $emp['doc_num'],
                'first_name'         => $emp['first_name'],
                'last_name'          => $emp['last_name'],
                'email'              => $emp['email'],
                'hire_date'          => $emp['hire_date'],
                'department_id'      => $deptIds[$emp['dept']],
                'position_id'        => $positionIds[$emp['pos']],
                'employment_type_id' => $empTypeIds[$emp['type']],
                'status'             => 'ACTIVE',
                'created_at'         => now(),
                'updated_at'         => now(),
            ]);

            // Crear usuario correspondiente
            DB::table('users')->insert([
                'company_id'    => $companyId,
                'employee_id'   => $empId,
                'role_id'       => $roles[$emp['role']],
                'username'      => strtolower($emp['first_name'] . '.' . $emp['last_name']),
                'email'         => $emp['email'],
                'password'      => Hash::make('Password123*'),
                'status'        => 'ACTIVE',
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }
}
