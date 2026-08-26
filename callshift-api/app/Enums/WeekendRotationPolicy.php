<?php

namespace App\Enums;

enum WeekendRotationPolicy: string
{
    case STRICT_ROTATION = 'STRICT_ROTATION';
    case FAIR_SHARE      = 'FAIR_SHARE';
    case NONE            = 'NONE';
}
