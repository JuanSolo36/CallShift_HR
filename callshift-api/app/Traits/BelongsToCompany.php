<?php

namespace App\Traits;

use App\Scopes\TenantScope;
use App\Models\Company;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

trait BelongsToCompany
{
    /**
     * Boot the trait to attach global TenantScope, enforce company_id on creation,
     * and prevent unauthorized mutation of company_id on update.
     */
    protected static function bootBelongsToCompany(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            // Si el usuario está autenticado, forzar incondicionalmente su company_id
            if (Auth::check() && Auth::user()->company_id) {
                $model->company_id = Auth::user()->company_id;
            }
        });

        static::updating(function ($model) {
            // Prevenir la mutación de company_id vía PUT/PATCH
            if ($model->isDirty('company_id') && Auth::check() && !Auth::user()->hasRole('SUPER_ADMIN')) {
                $model->company_id = $model->getOriginal('company_id');
            }
        });
    }

    /**
     * Relación con la Empresa matriz.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
