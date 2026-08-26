<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;
use App\Enums\ModificationType;

class ScheduleModification extends Model
{
    use Auditable;

    protected $fillable = [
        'schedule_assignment_id',
        'schedule_version_id',
        'employee_id',
        'modification_type',
        'previous_data',
        'new_data',
        'reason',
        'created_by',
        'approved_by',
    ];

    protected $casts = [
        'modification_type' => ModificationType::class,
        'previous_data'     => 'array',
        'new_data'          => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class, 'schedule_assignment_id');
    }

    public function scheduleAssignment(): BelongsTo
    {
        return $this->belongsTo(ScheduleAssignment::class, 'schedule_assignment_id');
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'schedule_version_id');
    }

    public function scheduleVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'schedule_version_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function evidences(): HasMany
    {
        return $this->hasMany(ModificationEvidence::class);
    }
}
