<?php

namespace App\Enums;

enum ConflictStatus: string
{
    case ACTIVE       = 'ACTIVE';
    case RESOLVED     = 'RESOLVED';
    case AUTO_CLEARED = 'AUTO_CLEARED';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE       => 'Activo',
            self::RESOLVED     => 'Resuelto / Exceptuado',
            self::AUTO_CLEARED => 'Autocorregido por modificación de turno',
        };
    }
}
