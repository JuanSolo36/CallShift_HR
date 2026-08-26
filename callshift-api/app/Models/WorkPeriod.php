<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;
use App\Enums\WorkPeriodStatus;
use App\Enums\WorkPeriodType;

class WorkPeriod extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'department_id',
        'name',
        'period_type',
        'start_date',
        'end_date',
        'status',
        'current_version_id',
        'created_by',
    ];

    protected $casts = [
        'period_type' => WorkPeriodType::class,
        'start_date'  => 'date:Y-m-d',
        'end_date'    => 'date:Y-m-d',
        'status'      => WorkPeriodStatus::class,
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ScheduleVersion::class)->orderBy('version_number', 'desc');
    }
}
