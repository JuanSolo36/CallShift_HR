<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class ShiftPattern extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'department_id',
        'position_id',
        'name',
        'code',
        'cycle_length_days',
        'description',
        'status',
        'created_by',
    ];

    protected $casts = [
        'cycle_length_days' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ShiftPatternEntry::class, 'shift_pattern_id')->orderBy('day_number', 'asc');
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ShiftTemplate::class, 'shift_pattern_id');
    }
}
