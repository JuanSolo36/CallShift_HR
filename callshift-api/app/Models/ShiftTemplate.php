<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class ShiftTemplate extends Model
{
    use SoftDeletes, BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'department_id',
        'position_id',
        'shift_pattern_id',
        'name',
        'code',
        'description',
        'status',
        'metadata',
        'created_by',
    ];

    protected $casts = [
        'metadata' => 'array',
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

    public function pattern(): BelongsTo
    {
        return $this->belongsTo(ShiftPattern::class, 'shift_pattern_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
