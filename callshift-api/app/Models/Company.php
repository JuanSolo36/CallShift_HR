<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;

class Company extends Model
{
    use SoftDeletes, Auditable;

    protected $fillable = [
        'name',
        'legal_name',
        'tax_id',
        'slug',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'timezone',
        'currency',
        'date_format',
        'logo',
        'primary_color',
        'secondary_color',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(Position::class);
    }

    public function employmentTypes(): HasMany
    {
        return $this->hasMany(EmploymentType::class);
    }

    public function shiftTypes(): HasMany
    {
        return $this->hasMany(ShiftType::class);
    }

    public function workPeriods(): HasMany
    {
        return $this->hasMany(WorkPeriod::class);
    }

    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function absences(): HasMany
    {
        return $this->hasMany(Absence::class);
    }

    public function businessRules(): HasMany
    {
        return $this->hasMany(BusinessRule::class);
    }

    public function holidays(): HasMany
    {
        return $this->hasMany(Holiday::class);
    }

    public function systemSettings(): HasMany
    {
        return $this->hasMany(SystemSetting::class);
    }
}
