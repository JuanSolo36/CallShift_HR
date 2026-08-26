<?php

namespace App\Enums;

enum AbsenceType: string
{
    case SICK_LEAVE          = 'SICK_LEAVE';
    case VACATION            = 'VACATION';
    case PERMISSION          = 'PERMISSION';
    case BEREAVEMENT         = 'BEREAVEMENT';
    case UNEXCUSED           = 'UNEXCUSED';
    case MATERNITY_PATERNITY = 'MATERNITY_PATERNITY';
    case OTHER               = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SICK_LEAVE          => 'Incapacidad Médica',
            self::VACATION            => 'Vacaciones',
            self::PERMISSION          => 'Permiso Remunerado / No Remunerado',
            self::BEREAVEMENT         => 'Calamidad Doméstica / Duelo',
            self::UNEXCUSED           => 'Ausencia No Justificada',
            self::MATERNITY_PATERNITY => 'Licencia de Maternidad / Paternidad',
            self::OTHER               => 'Otra Novedad',
        };
    }

    public function blocksScheduling(): bool
    {
        return true;
    }
}
