<?php

namespace App\Enums;

enum WorkPeriodStatus: string
{
    case DRAFT     = 'DRAFT';
    case GENERATED = 'GENERATED';
    case REVIEW    = 'REVIEW';
    case PUBLISHED = 'PUBLISHED';
    case CLOSED    = 'CLOSED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Borrador Inicial',
            self::GENERATED => 'Generado Automáticamente',
            self::REVIEW    => 'En Revisión',
            self::PUBLISHED => 'Publicado y Vigente',
            self::CLOSED    => 'Cerrado / Histórico',
        };
    }
}
