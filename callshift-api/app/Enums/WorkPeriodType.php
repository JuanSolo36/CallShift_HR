<?php

namespace App\Enums;

enum WorkPeriodType: string
{
    case WEEKLY   = 'WEEKLY';
    case BIWEEKLY = 'BIWEEKLY';
    case MONTHLY  = 'MONTHLY';
    case CUSTOM   = 'CUSTOM';

    public function label(): string
    {
        return match ($this) {
            self::WEEKLY   => 'Semanal (7 días)',
            self::BIWEEKLY => 'Quincenal (14-15 días)',
            self::MONTHLY  => 'Mensual (30-31 días)',
            self::CUSTOM   => 'Personalizado',
        };
    }
}
