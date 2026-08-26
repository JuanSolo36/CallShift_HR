<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;
use App\Enums\AbsenceType;
use App\Enums\AbsenceStatus;

class Absence extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_request_id',
        'type',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'is_full_day',
        'reason',
        'status',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'is_full_day' => 'boolean',
        'type'        => AbsenceType::class,
        'status'      => AbsenceStatus::class,
        'approved_at' => 'datetime',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', AbsenceStatus::APPROVED);
    }
}
