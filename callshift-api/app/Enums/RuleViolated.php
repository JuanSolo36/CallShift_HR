<?php

namespace App\Enums;

enum RuleViolated: string
{
    case OVERLAPPING_SHIFTS             = 'OVERLAPPING_SHIFTS';
    case MIN_REST_BETWEEN_SHIFTS         = 'MIN_REST_BETWEEN_SHIFTS';
    case MAX_CONSECUTIVE_WORK_DAYS       = 'MAX_CONSECUTIVE_WORK_DAYS';
    case MAX_DAILY_HOURS                = 'MAX_DAILY_HOURS';
    case MIN_DAILY_HOURS                = 'MIN_DAILY_HOURS';
    case LEGAL_WEEKLY_HOURS_EXCEEDED    = 'LEGAL_WEEKLY_HOURS_EXCEEDED';
    case CONTRACT_WEEKLY_HOURS_EXCEEDED = 'CONTRACT_WEEKLY_HOURS_EXCEEDED';
    case MIN_WEEKLY_HOURS               = 'MIN_WEEKLY_HOURS';
    case APPROVED_ABSENCE_COLLISION     = 'APPROVED_ABSENCE_COLLISION';
    case UNAVAILABLE_RESTRICTION        = 'UNAVAILABLE_RESTRICTION';
    case WEEKEND_ROTATION_VIOLATION     = 'WEEKEND_ROTATION_VIOLATION';
    case NIGHT_SHIFT_DISALLOWED         = 'NIGHT_SHIFT_DISALLOWED';

    public function defaultSeverity(): ConflictSeverity
    {
        return match ($this) {
            self::OVERLAPPING_SHIFTS             => ConflictSeverity::HARD_CONFLICT,
            self::MIN_REST_BETWEEN_SHIFTS        => ConflictSeverity::HARD_CONFLICT,
            self::MAX_CONSECUTIVE_WORK_DAYS      => ConflictSeverity::HARD_CONFLICT,
            self::MAX_DAILY_HOURS               => ConflictSeverity::HARD_CONFLICT,
            self::MIN_DAILY_HOURS               => ConflictSeverity::SOFT_WARNING,
            self::LEGAL_WEEKLY_HOURS_EXCEEDED   => ConflictSeverity::HARD_CONFLICT,
            self::CONTRACT_WEEKLY_HOURS_EXCEEDED=> ConflictSeverity::SOFT_WARNING,
            self::MIN_WEEKLY_HOURS              => ConflictSeverity::SOFT_WARNING,
            self::APPROVED_ABSENCE_COLLISION    => ConflictSeverity::HARD_CONFLICT,
            self::UNAVAILABLE_RESTRICTION       => ConflictSeverity::HARD_CONFLICT,
            self::WEEKEND_ROTATION_VIOLATION    => ConflictSeverity::SOFT_WARNING,
            self::NIGHT_SHIFT_DISALLOWED        => ConflictSeverity::HARD_CONFLICT,
        };
    }
}
