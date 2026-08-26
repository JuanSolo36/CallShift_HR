<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE     = 'ACTIVE';
    case INACTIVE   = 'INACTIVE';
    case ON_LEAVE   = 'ON_LEAVE';
    case TERMINATED = 'TERMINATED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE     => 'Activo',
            self::INACTIVE   => 'Inactivo',
            self::ON_LEAVE   => 'En Licencia / Ausencia',
            self::TERMINATED => 'Retirado',
        };
    }

    public function isSchedulable(): bool
    {
        return $this === self::ACTIVE;
    }
}
