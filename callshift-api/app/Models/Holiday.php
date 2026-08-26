<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class Holiday extends Model
{
    use BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'date',
        'name',
        'is_mandatory_rest',
    ];

    protected $casts = [
        'date'              => 'date',
        'is_mandatory_rest' => 'boolean',
    ];
}
