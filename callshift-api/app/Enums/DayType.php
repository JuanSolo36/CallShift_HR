<?php

namespace App\Enums;

enum DayType: string
{
    case WORK       = 'WORK';
    case REST       = 'REST';
    case OFF        = 'OFF';
    case HOLIDAY    = 'HOLIDAY';
    case PERMISSION = 'PERMISSION';
    case ABSENCE    = 'ABSENCE';

    public function label(): string
    {
        return match ($this) {
            self::WORK       => 'Jornada Laboral',
            self::REST       => 'Descanso Programado',
            self::OFF        => 'Día Libre / No Laboral',
            self::HOLIDAY    => 'Festivo',
            self::PERMISSION => 'Permiso Concedido',
            self::ABSENCE    => 'Incapacidad / Ausencia',
        };
    }

    public function isWorking(): bool
    {
        return $this === self::WORK;
    }
}
