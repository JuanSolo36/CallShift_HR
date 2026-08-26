<?php

namespace App\Models;

use App\Enums\WeekendRotationPolicy;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessRule extends Model
{
    use BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'department_id',
        'max_daily_hours',
        'min_daily_hours',
        'max_weekly_hours',
        'min_weekly_hours',
        'min_rest_hours_between_shifts',
        'max_consecutive_work_days',
        'allow_night_shifts',
        'weekend_rotation_policy',
    ];

    protected $casts = [
        'department_scope_id'           => 'integer',
        'max_daily_hours'               => 'decimal:1',
        'min_daily_hours'               => 'decimal:1',
        'max_weekly_hours'              => 'decimal:1',
        'min_weekly_hours'              => 'decimal:1',
        'min_rest_hours_between_shifts' => 'decimal:1',
        'max_consecutive_work_days'     => 'integer',
        'allow_night_shifts'            => 'boolean',
        'weekend_rotation_policy'       => WeekendRotationPolicy::class,
    ];

    protected static function booted(): void
    {
        // Invariante de seguridad: department_scope_id siempre se deriva automáticamente de department_id
        static::saving(function (BusinessRule $rule) {
            $rule->department_scope_id = $rule->department_id ? (int) $rule->department_id : 0;
        });
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
