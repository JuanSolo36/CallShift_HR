<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Builder;
use App\Enums\AuditAction;
use BadMethodCallException;

class AuditLog extends Model
{
    public $timestamps = false; // Solo maneja created_at inmutable

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'action'     => AuditAction::class,
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Bloqueo a nivel de modelo: Los registros de auditoría son inmutables (Append-Only).
     */
    protected static function booted(): void
    {
        static::updating(function () {
            throw new BadMethodCallException('Los registros de auditoría son inmutables (Append-Only) y no pueden ser modificados.');
        });

        static::deleting(function () {
            throw new BadMethodCallException('Los registros de auditoría son inmutables (Append-Only) y no pueden ser eliminados.');
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope para filtrar registros por empresa.
     */
    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope para aplicar filtros avanzados de búsqueda y auditoría.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when(!empty($filters['user_id']), function (Builder $q) use ($filters) {
                $q->where('user_id', (int)$filters['user_id']);
            })
            ->when(!empty($filters['action']), function (Builder $q) use ($filters) {
                $actionVal = $filters['action'] instanceof AuditAction ? $filters['action']->value : (string)$filters['action'];
                $q->where('action', $actionVal);
            })
            ->when(!empty($filters['auditable_type']), function (Builder $q) use ($filters) {
                $type = (string)$filters['auditable_type'];
                if (!str_contains($type, '\\')) {
                    $q->where(function (Builder $sub) use ($type) {
                        $sub->where('auditable_type', 'like', "%{$type}%")
                            ->orWhere('auditable_type', "App\\Models\\{$type}");
                    });
                } else {
                    $q->where('auditable_type', $type);
                }
            })
            ->when(!empty($filters['auditable_id']), function (Builder $q) use ($filters) {
                $q->where('auditable_id', (int)$filters['auditable_id']);
            })
            ->when(!empty($filters['date_from']), function (Builder $q) use ($filters) {
                $q->where('created_at', '>=', $filters['date_from']);
            })
            ->when(!empty($filters['date_to']), function (Builder $q) use ($filters) {
                $q->where('created_at', '<=', $filters['date_to'] . (strlen($filters['date_to']) <= 10 ? ' 23:59:59' : ''));
            })
            ->when(!empty($filters['search']), function (Builder $q) use ($filters) {
                $term = "%{$filters['search']}%";
                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('description', 'like', $term)
                        ->orWhere('auditable_type', 'like', $term)
                        ->orWhere('ip_address', 'like', $term);
                });
            });
    }
}
