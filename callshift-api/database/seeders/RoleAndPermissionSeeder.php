<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Enums\RoleCode;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles del Sistema
        $rolesData = [
            [
                'code'        => RoleCode::SUPER_ADMIN->value,
                'name'        => 'Super Administrador',
                'description' => 'Acceso total a la configuración de la empresa, usuarios, auditoría y todos los módulos.',
                'is_system'   => true,
            ],
            [
                'code'        => RoleCode::HR_ADMIN->value,
                'name'        => 'Administrador de Recursos Humanos',
                'description' => 'Gestión completa de empleados, estructura, contratos, ausencias y reportes consolidados.',
                'is_system'   => true,
            ],
            [
                'code'        => RoleCode::MANAGER->value,
                'name'        => 'Gerente de Departamento',
                'description' => 'Gestión y planificación de horarios, aprobación de ausencias y reportes de su área.',
                'is_system'   => true,
            ],
            [
                'code'        => RoleCode::SUPERVISOR->value,
                'name'        => 'Supervisor de Turno',
                'description' => 'Supervisión de su equipo asignado, edición de borradores y solicitud de modificaciones.',
                'is_system'   => true,
            ],
            [
                'code'        => RoleCode::EMPLOYEE->value,
                'name'        => 'Empleado',
                'description' => 'Consulta de horarios asignados, registro de disponibilidad y radicación de solicitudes.',
                'is_system'   => true,
            ],
            [
                'code'        => RoleCode::VIEWER->value,
                'name'        => 'Visualizador (Auditor / Solo Lectura)',
                'description' => 'Acceso de solo lectura para auditoría interna y revisión sin privilegios de modificación.',
                'is_system'   => true,
            ],
        ];

        $roleIds = [];
        foreach ($rolesData as $role) {
            $id = DB::table('roles')->insertGetId([
                'company_id'  => null, // Roles globales de plantilla
                'code'        => $role['code'],
                'name'        => $role['name'],
                'description' => $role['description'],
                'is_system'   => $role['is_system'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $roleIds[$role['code']] = $id;
        }

        // 2. Catálogo Extenso de Permisos
        $permissions = [
            // Empleados
            ['module' => 'employees', 'action' => 'view', 'code' => 'employees:view', 'description' => 'Ver listado y fichas de empleados'],
            ['module' => 'employees', 'action' => 'create', 'code' => 'employees:create', 'description' => 'Registrar nuevos empleados'],
            ['module' => 'employees', 'action' => 'update', 'code' => 'employees:update', 'description' => 'Editar información de empleados'],
            ['module' => 'employees', 'action' => 'delete', 'code' => 'employees:delete', 'description' => 'Dar de baja o desactivar empleados'],
            
            // Estructura Organizacional
            ['module' => 'organization', 'action' => 'view', 'code' => 'organization:view', 'description' => 'Ver departamentos, cargos y tipos de contrato'],
            ['module' => 'organization', 'action' => 'manage', 'code' => 'organization:manage', 'description' => 'Crear y modificar estructura organizacional'],

            // Tipos de Turno
            ['module' => 'shifts', 'action' => 'view', 'code' => 'shifts:view', 'description' => 'Consultar tipos de turno'],
            ['module' => 'shifts', 'action' => 'manage', 'code' => 'shifts:manage', 'description' => 'Crear, editar y configurar tipos de turno'],

            // Planificación y Horarios
            ['module' => 'schedules', 'action' => 'view', 'code' => 'schedules:view', 'description' => 'Visualizar periodos y mallas de horarios'],
            ['module' => 'schedules', 'action' => 'create', 'code' => 'schedules:create', 'description' => 'Crear nuevos periodos y horarios manuales'],
            ['module' => 'schedules', 'action' => 'update', 'code' => 'schedules:update', 'description' => 'Modificar celdas en borradores de horario'],
            ['module' => 'schedules', 'action' => 'generate', 'code' => 'schedules:generate', 'description' => 'Ejecutar motor generador automático'],
            ['module' => 'schedules', 'action' => 'publish', 'code' => 'schedules:publish', 'description' => 'Publicar versiones oficiales inmutables'],
            ['module' => 'schedules', 'action' => 'modify', 'code' => 'schedules:modify', 'description' => 'Modificar turnos sobre horarios publicados (crea V_next)'],
            ['module' => 'schedules', 'action' => 'restore', 'code' => 'schedules:restore', 'description' => 'Restaurar versiones históricas'],

            // Disponibilidad
            ['module' => 'availability', 'action' => 'view', 'code' => 'availability:view', 'description' => 'Consultar disponibilidad'],
            ['module' => 'availability', 'action' => 'manage', 'code' => 'availability:manage', 'description' => 'Gestionar disponibilidad y restricciones'],

            // Ausencias y Novedades
            ['module' => 'absences', 'action' => 'view', 'code' => 'absences:view', 'description' => 'Ver ausencias e incapacidades'],
            ['module' => 'absences', 'action' => 'create', 'code' => 'absences:create', 'description' => 'Registrar ausencias y licencias'],
            ['module' => 'absences', 'action' => 'approve', 'code' => 'absences:approve', 'description' => 'Aprobar o rechazar solicitudes de ausencia'],

            // Reportes
            ['module' => 'reports', 'action' => 'view', 'code' => 'reports:view', 'description' => 'Consultar reportes y dashboards'],
            ['module' => 'reports', 'action' => 'export', 'code' => 'reports:export', 'description' => 'Exportar datos a Excel, CSV y PDF'],

            // Auditoría
            ['module' => 'audit', 'action' => 'view', 'code' => 'audit:view', 'description' => 'Consultar bitácora forense de auditoría'],

            // Administración de Usuarios y Empresa
            ['module' => 'users', 'action' => 'manage', 'code' => 'users:manage', 'description' => 'Gestionar usuarios, roles y asignaciones'],
            ['module' => 'settings', 'action' => 'manage', 'code' => 'settings:manage', 'description' => 'Configurar parámetros empresariales y reglas'],
        ];

        $permissionIds = [];
        foreach ($permissions as $perm) {
            $id = DB::table('permissions')->insertGetId([
                'module'      => $perm['module'],
                'action'      => $perm['action'],
                'code'        => $perm['code'],
                'description' => $perm['description'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
            $permissionIds[$perm['code']] = $id;
        }

        // 3. Asignación de Permisos por Rol
        
        // SUPER_ADMIN: Todos los permisos
        foreach ($permissionIds as $pId) {
            DB::table('role_permissions')->insert([
                'role_id'       => $roleIds['SUPER_ADMIN'],
                'permission_id' => $pId,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }

        // HR_ADMIN
        $hrPerms = [
            'employees:view', 'employees:create', 'employees:update', 'employees:delete',
            'organization:view', 'organization:manage',
            'shifts:view', 'shifts:manage',
            'schedules:view', 'schedules:create', 'schedules:update', 'schedules:generate', 'schedules:publish', 'schedules:modify', 'schedules:restore',
            'availability:view', 'availability:manage',
            'absences:view', 'absences:create', 'absences:approve',
            'reports:view', 'reports:export',
            'audit:view',
        ];
        foreach ($hrPerms as $code) {
            if (isset($permissionIds[$code])) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleIds['HR_ADMIN'],
                    'permission_id' => $permissionIds[$code],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        // MANAGER
        $managerPerms = [
            'employees:view',
            'organization:view',
            'shifts:view',
            'schedules:view', 'schedules:create', 'schedules:update', 'schedules:generate', 'schedules:publish', 'schedules:modify',
            'availability:view', 'availability:manage',
            'absences:view', 'absences:create', 'absences:approve',
            'reports:view', 'reports:export',
        ];
        foreach ($managerPerms as $code) {
            if (isset($permissionIds[$code])) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleIds['MANAGER'],
                    'permission_id' => $permissionIds[$code],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        // SUPERVISOR
        $supervisorPerms = [
            'employees:view',
            'organization:view',
            'shifts:view',
            'schedules:view', 'schedules:update',
            'availability:view',
            'absences:view', 'absences:create',
            'reports:view', 'reports:export',
        ];
        foreach ($supervisorPerms as $code) {
            if (isset($permissionIds[$code])) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleIds['SUPERVISOR'],
                    'permission_id' => $permissionIds[$code],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        // EMPLOYEE
        $employeePerms = [
            'schedules:view',
            'availability:view', 'availability:manage',
            'absences:view', 'absences:create',
        ];
        foreach ($employeePerms as $code) {
            if (isset($permissionIds[$code])) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleIds['EMPLOYEE'],
                    'permission_id' => $permissionIds[$code],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }

        // VIEWER
        $viewerPerms = [
            'employees:view',
            'organization:view',
            'shifts:view',
            'schedules:view',
            'availability:view',
            'absences:view',
            'reports:view',
            'audit:view',
        ];
        foreach ($viewerPerms as $code) {
            if (isset($permissionIds[$code])) {
                DB::table('role_permissions')->insert([
                    'role_id'       => $roleIds['VIEWER'],
                    'permission_id' => $permissionIds[$code],
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
