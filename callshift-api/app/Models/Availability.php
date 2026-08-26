<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\Auditable;

class Availability extends Model
{
    use Auditable;

    protected $fillable = [
        'employee_id',
        'type',
        'day_of_week',
        'specific_date',
        'is_available',
        'start_time',
        'end_time',
        'priority',
        'notes',
        'status',
    ];

    protected $casts = [
        'day_of_week'   => 'integer',
        'specific_date' => 'date',
        'is_available'  => 'boolean',
    ];

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
