<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class EmploymentType extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'default_weekly_hours',
        'description',
        'status',
    ];

    protected $casts = [
        'default_weekly_hours' => 'float',
    ];

    public function getWeeklyHoursBaseAttribute(): ?float
    {
        return $this->default_weekly_hours;
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
