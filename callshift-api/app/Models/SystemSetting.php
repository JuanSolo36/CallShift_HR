<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToCompany;
use App\Traits\Auditable;

class SystemSetting extends Model
{
    use BelongsToCompany, Auditable;

    protected $fillable = [
        'company_id',
        'key',
        'value',
        'type',
    ];
}
