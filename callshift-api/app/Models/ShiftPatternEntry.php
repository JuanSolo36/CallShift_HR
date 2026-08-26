<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\DayType;

class ShiftPatternEntry extends Model
{
    protected $fillable = [
        'shift_pattern_id',
        'day_number',
        'day_type',
        'shift_type_id',
        'start_time_override',
        'end_time_override',
        'notes',
    ];

    protected $casts = [
        'day_number' => 'integer',
        'day_type'   => DayType::class,
    ];

    public function pattern(): BelongsTo
    {
        return $this->belongsTo(ShiftPattern::class, 'shift_pattern_id');
    }

    public function shiftType(): BelongsTo
    {
        return $this->belongsTo(ShiftType::class, 'shift_type_id');
    }
}
