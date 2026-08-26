<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot auditable events on Eloquent models.
     */
    protected static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->recordAuditLog(AuditAction::CREATE, null, $model->getAttributes());
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            // Symmetric diff: old_values only contains the previous values of changed attributes
            $oldValues = array_intersect_key($model->getOriginal(), $changes);
            $model->recordAuditLog(AuditAction::UPDATE, $oldValues, $changes);
        });

        static::deleted(function ($model) {
            $model->recordAuditLog(AuditAction::DELETE, $model->getOriginal(), null);
        });
    }

    /**
     * Registra una entrada inmutable en la bitácora de auditoría.
     */
    public function recordAuditLog(AuditAction $action, ?array $oldValues = null, ?array $newValues = null, ?string $description = null): void
    {
        $user = Auth::user();
        $companyId = $this->company_id 
            ?? ($this->workPeriod?->company_id 
            ?? ($this->scheduleVersion?->workPeriod?->company_id 
            ?? ($user?->company_id ?? 1)));
            
        $connName = method_exists($this, 'getConnectionName') ? $this->getConnectionName() : null;

        $sanitizedOld = $oldValues ? AuditService::sanitizeValues($oldValues) : null;
        $sanitizedNew = $newValues ? AuditService::sanitizeValues($newValues) : null;

        AuditService::log(
            $action,
            get_class($this),
            $this->id ?? null,
            $description ?? "{$action->label()} en " . class_basename($this),
            $sanitizedOld,
            $sanitizedNew,
            $companyId,
            $connName
        );
    }
}
