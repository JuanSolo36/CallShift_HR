<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Traits\Auditable;
use App\Enums\DayType;
use DomainException;

class ScheduleAssignment extends Model
{
    use Auditable;

    protected $fillable = [
        'schedule_version_id',
        'employee_id',
        'date',
        'day_type',
        'shift_type_id',
        'start_time',
        'end_time',
        'starts_at',
        'ends_at',
        'break_start',
        'break_end',
        'total_hours',
        'is_custom',
        'notes',
    ];

    protected $casts = [
        'starts_at'   => 'datetime',
        'ends_at'     => 'datetime',
        'day_type'    => DayType::class,
        'total_hours' => 'float',
        'is_custom'   => 'boolean',
    ];

    public function setDateAttribute($value): void
    {
        $this->attributes['date'] = $value instanceof \DateTimeInterface
            ? $value->format('Y-m-d')
            : substr((string) $value, 0, 10);
    }

    public function getDateAttribute($value)
    {
        if (empty($value)) {
            return null;
        }
        return is_string($value) ? \Carbon\CarbonImmutable::parse(substr($value, 0, 10)) : $value;
    }

    /**
     * Inmutabilidad multinivel en capa de modelo: Prevenir mutaciones sobre versiones publicadas/archivadas.
     */
    protected static function booted(): void
    {
        static::saving(function (ScheduleAssignment $assignment) {
            $version = $assignment->version ?: ScheduleVersion::on($assignment->getConnectionName())->find($assignment->schedule_version_id);
            if ($version && $version->isImmutable()) {
                throw new DomainException("Violación de inmutabilidad: No se pueden crear ni modificar asignaciones en versiones {$version->status->value}.");
            }
        });

        static::deleting(function (ScheduleAssignment $assignment) {
            $version = $assignment->version ?: ScheduleVersion::on($assignment->getConnectionName())->find($assignment->schedule_version_id);
            if ($version && $version->isImmutable()) {
                throw new DomainException("Violación de inmutabilidad: No se pueden eliminar asignaciones en versiones {$version->status->value}.");
            }
        });
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(ScheduleVersion::class, 'schedule_version_id');
    }

    public function scheduleVersion(): BelongsTo
    {
        return $this->version();
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function shiftType(): BelongsTo
    {
        return $this->belongsTo(ShiftType::class);
    }

    public function modifications(): HasMany
    {
        return $this->hasMany(ScheduleModification::class);
    }
}
