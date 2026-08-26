<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class ShiftType extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'color_hex',
        'start_time',
        'end_time',
        'break_duration_minutes',
        'total_work_hours',
        'crosses_midnight',
        'description',
        'status',
    ];

    protected $casts = [
        'break_duration_minutes' => 'integer',
        'total_work_hours'       => 'float',
        'crosses_midnight'       => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }
}
