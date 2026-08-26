<?php

namespace App\Enums;

enum ModificationType: string
{
    case SHIFT_SWAP                = 'SHIFT_SWAP';
    case SHIFT_CHANGE              = 'SHIFT_CHANGE';
    case TIME_CHANGE               = 'TIME_CHANGE';
    case WORKDAY_CHANGE            = 'WORKDAY_CHANGE';
    case DAY_OFF_CHANGE            = 'DAY_OFF_CHANGE';
    case REST_DAY_CHANGE           = 'REST_DAY_CHANGE';
    case LEAVE_PERMISSION          = 'LEAVE_PERMISSION';
    case ABSENCE_COVERAGE          = 'ABSENCE_COVERAGE';
    case ABSENCE                   = 'ABSENCE';
    case ADMINISTRATIVE_ADJUSTMENT = 'ADMINISTRATIVE_ADJUSTMENT';
    case OTHER                     = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::SHIFT_SWAP                => 'Intercambio de Turno entre Empleados',
            self::SHIFT_CHANGE              => 'Cambio de Turno Asignado',
            self::TIME_CHANGE               => 'Ajuste de Horario (Entrada/Salida)',
            self::WORKDAY_CHANGE            => 'Cambio de Jornada Laboral',
            self::DAY_OFF_CHANGE            => 'Cambio de Día de Descanso',
            self::REST_DAY_CHANGE           => 'Ajuste de Día Libre / Franco',
            self::LEAVE_PERMISSION          => 'Permiso o Licencia Laboral',
            self::ABSENCE_COVERAGE          => 'Cubrimiento por Ausencia / Novedad',
            self::ABSENCE                   => 'Registro de Ausencia Justificada',
            self::ADMINISTRATIVE_ADJUSTMENT => 'Ajuste Administrativo Directo',
            self::OTHER                     => 'Otro Motivo Justificado',
        };
    }
}
