<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;
use App\Enums\ScheduleVersionStatus;
use DomainException;

class ScheduleVersion extends Model
{
    use Auditable;

    protected $fillable = [
        'work_period_id',
        'version_number',
        'status',
        'parent_version_id',
        'change_summary',
        'score',
        'hard_conflicts_count',
        'soft_conflicts_count',
        'lock_version',
        'created_by',
        'published_by',
        'published_at',
    ];

    protected $casts = [
        'version_number'       => 'integer',
        'status'               => ScheduleVersionStatus::class,
        'score'                => 'float',
        'hard_conflicts_count' => 'integer',
        'soft_conflicts_count' => 'integer',
        'lock_version'         => 'integer',
        'published_at'         => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (ScheduleVersion $version) {
            $originalStatus = $version->getOriginal('status');
            $originalStatusValue = $originalStatus instanceof ScheduleVersionStatus ? $originalStatus->value : (string)$originalStatus;
            $newStatusValue = $version->status instanceof ScheduleVersionStatus ? $version->status->value : (string)$version->status;

            if ($originalStatusValue === ScheduleVersionStatus::ARCHIVED->value) {
                throw new DomainException('Violación de inmutabilidad: Las versiones archivadas (ARCHIVED) son terminales e inmutables.');
            }

            if ($originalStatusValue === ScheduleVersionStatus::PUBLISHED->value) {
                if ($newStatusValue !== ScheduleVersionStatus::ARCHIVED->value) {
                    throw new DomainException('Violación de inmutabilidad: Las versiones publicadas (PUBLISHED) solo pueden transicionar a ARCHIVED.');
                }
            }
        });

        static::deleting(function (ScheduleVersion $version) {
            if ($version->isImmutable()) {
                throw new DomainException("Violación de inmutabilidad: No se pueden eliminar versiones en estado {$version->status->value}.");
            }
        });
    }

    public function workPeriod(): BelongsTo
    {
        return $this->belongsTo(WorkPeriod::class);
    }

    public function parentVersion(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'parent_version_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ScheduleAssignment::class);
    }

    public function conflicts(): HasMany
    {
        return $this->hasMany(ScheduleConflict::class);
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(ScheduleModification::class);
    }

    public function isImmutable(): bool
    {
        return $this->status instanceof ScheduleVersionStatus
            ? $this->status->isImmutable()
            : in_array((string)$this->status, [ScheduleVersionStatus::PUBLISHED->value, ScheduleVersionStatus::ARCHIVED->value], true);
    }
}
