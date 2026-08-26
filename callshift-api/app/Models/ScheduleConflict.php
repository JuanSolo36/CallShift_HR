<?php

namespace App\Models;

use App\Enums\ConflictSeverity;
use App\Enums\ConflictStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduleConflict extends Model
{
    protected $fillable = [
        'schedule_version_id',
        'employee_id',
        'conflict_key',
        'date',
        'start_datetime',
        'end_datetime',
        'severity',
        'rule_violated',
        'description',
        'suggested_resolution',
        'primary_assignment_id',
        'conflicting_assignment_id',
        'status',
        'is_resolved',
        'resolved_by',
        'resolved_at',
        'resolution_reason',
    ];

    protected $casts = [
        'date'           => 'date:Y-m-d',
        'start_datetime' => 'datetime',
        'end_datetime'   => 'datetime',
        'severity'       => ConflictSeverity::class,
        'status'         => ConflictStatus::class,
        'is_resolved'    => 'boolean',
        'resolved_at'    => 'datetime',
    ];

    public function version(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'schedule_version_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function primaryAssignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class, 'primary_assignment_id');
    }

    public function conflictingAssignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class, 'conflicting_assignment_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ConflictStatus::ACTIVE);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('status', ConflictStatus::RESOLVED);
    }

    public function scopeHard(Builder $query): Builder
    {
        return $query->where('severity', ConflictSeverity::HARD_CONFLICT);
    }
}
