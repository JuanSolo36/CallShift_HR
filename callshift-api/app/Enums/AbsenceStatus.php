<?php

namespace App\Enums;

enum AbsenceStatus: string
{
    case PENDING   = 'PENDING';
    case APPROVED  = 'APPROVED';
    case REJECTED  = 'REJECTED';
    case CANCELLED = 'CANCELLED';

    public function label(): string
    {
        return match ($this) {
            self::PENDING   => 'Pendiente de Aprobación',
            self::APPROVED  => 'Aprobada',
            self::REJECTED  => 'Rechazada',
            self::CANCELLED => 'Cancelada',
        };
    }
}
