<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;
use App\Enums\EmployeeStatus;

class Employee extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'employee_code',
        'document_type',
        'document_number',
        'first_name',
        'middle_name',
        'last_name',
        'second_last_name',
        'email',
        'personal_email',
        'phone',
        'birth_date',
        'hire_date',
        'termination_date',
        'department_id',
        'position_id',
        'employment_type_id',
        'supervisor_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'birth_date'       => 'date',
        'hire_date'        => 'date',
        'termination_date' => 'date',
        'status'           => EmployeeStatus::class,
    ];

    protected $appends = ['full_name'];

    public function getFullNameAttribute(): string
    {
        return trim(preg_replace('/\s+/', ' ', "{$this->first_name} {$this->middle_name} {$this->last_name} {$this->second_last_name}"));
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function employmentType(): BelongsTo
    {
        return $this->belongsTo(EmploymentType::class);
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'supervisor_id');
    }

    public function subordinates(): HasMany
    {
        return $this->hasMany(Employee::class, 'supervisor_id');
    }

    public function user(): HasOne
    {
        return $this->hasOne(User::class);
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(Availability::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function scheduleAssignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', EmployeeStatus::ACTIVE);
    }
}
