<?php

namespace App\Enums;

enum RoleCode: string
{
    case SUPER_ADMIN = 'SUPER_ADMIN';
    case HR_ADMIN    = 'HR_ADMIN';
    case MANAGER     = 'MANAGER';
    case SUPERVISOR  = 'SUPERVISOR';
    case EMPLOYEE    = 'EMPLOYEE';
    case VIEWER      = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::SUPER_ADMIN => 'Super Administrador',
            self::HR_ADMIN    => 'Administrador de RRHH',
            self::MANAGER     => 'Gerente de Departamento',
            self::SUPERVISOR  => 'Supervisor de Turno',
            self::EMPLOYEE    => 'Empleado',
            self::VIEWER      => 'Visualizador (Solo Lectura)',
        };
    }
}
