<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;
use App\Enums\AbsenceType;
use App\Enums\AbsenceStatus;

class LeaveRequest extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'is_full_day' => 'boolean',
        'type'        => AbsenceType::class,
        'status'      => AbsenceStatus::class,
        'reviewed_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function absence(): HasOne
    {
        return $this->hasOne(Absence::class);
    }
}
