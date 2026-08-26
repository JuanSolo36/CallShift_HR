<?php

namespace App\Enums;

enum ScheduleVersionStatus: string
{
    case DRAFT     = 'DRAFT';
    case REVIEW    = 'REVIEW';
    case PUBLISHED = 'PUBLISHED';
    case ARCHIVED  = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT     => 'Borrador de Trabajo',
            self::REVIEW    => 'En Revisión de Supervisión',
            self::PUBLISHED => 'Publicada (Inmutable)',
            self::ARCHIVED  => 'Archivada (Histórica)',
        };
    }

    public function isImmutable(): bool
    {
        return in_array($this, [self::PUBLISHED, self::ARCHIVED], true);
    }
}
