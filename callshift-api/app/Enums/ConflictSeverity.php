<?php

namespace App\Enums;

enum ConflictSeverity: string
{
    case HARD_CONFLICT = 'HARD_CONFLICT';
    case SOFT_WARNING  = 'SOFT_WARNING';

    public function label(): string
    {
        return match ($this) {
            self::HARD_CONFLICT => 'Conflicto Crítico (Bloqueante)',
            self::SOFT_WARNING  => 'Advertencia (No Bloqueante)',
        };
    }
}
