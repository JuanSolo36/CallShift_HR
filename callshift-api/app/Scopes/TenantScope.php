<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    /**
     * Aplica el filtro global de aislamiento por empresa (company_id).
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (Auth::check() && Auth::user()->company_id !== null) {
            $builder->where($model->qualifyColumn('company_id'), Auth::user()->company_id);
        }
    }
}
