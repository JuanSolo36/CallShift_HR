<?php

namespace App\Services\Conflicts;

use App\Enums\AuditAction;
use App\Enums\WeekendRotationPolicy;
use App\Models\BusinessRule;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class BusinessRuleService
{
    public const SYSTEM_DEFAULTS = [
        'max_daily_hours'               => 10.0,
        'min_daily_hours'               => 4.0,
        'max_weekly_hours'              => 48.0,
        'min_weekly_hours'              => 20.0,
        'min_rest_hours_between_shifts' => 12.0,
        'max_consecutive_work_days'     => 6,
        'allow_night_shifts'            => true,
        'weekend_rotation_policy'       => WeekendRotationPolicy::FAIR_SHARE,
    ];

    /**
     * Resuelve un mapa en memoria de todas las reglas para la empresa (global y departamentales).
     */
    public function getRulesMapForCompany(int $companyId): array
    {
        $rules = BusinessRule::where('company_id', $companyId)->get();
        $map = [];
        foreach ($rules as $r) {
            $map[(int)$r->department_scope_id] = $r;
        }
        return $map;
    }

    /**
     * Resuelve las reglas efectivas para un colaborador con herencia campo a campo.
     * Jerarquía: Department Rule -> Global Company Rule -> System Defaults
     */
    public function resolveEffectiveRule(Employee $employee, ?array $rulesMap = null): object
    {
        $companyId    = (int) $employee->company_id;
        $departmentId = $employee->department_id ? (int) $employee->department_id : null;

        if ($rulesMap !== null) {
            $deptRule   = $departmentId ? ($rulesMap[$departmentId] ?? null) : null;
            $globalRule = $rulesMap[0] ?? null;
        } else {
            $deptRule = $departmentId
                ? BusinessRule::where('company_id', $companyId)->where('department_scope_id', $departmentId)->first()
                : null;
            $globalRule = BusinessRule::where('company_id', $companyId)->where('department_scope_id', 0)->first();
        }

        return (object) [
            'max_daily_hours'               => (float) ($deptRule?->max_daily_hours ?? $globalRule?->max_daily_hours ?? self::SYSTEM_DEFAULTS['max_daily_hours']),
            'min_daily_hours'               => (float) ($deptRule?->min_daily_hours ?? $globalRule?->min_daily_hours ?? self::SYSTEM_DEFAULTS['min_daily_hours']),
            'max_weekly_hours'              => (float) ($deptRule?->max_weekly_hours ?? $globalRule?->max_weekly_hours ?? self::SYSTEM_DEFAULTS['max_weekly_hours']),
            'min_weekly_hours'              => (float) ($deptRule?->min_weekly_hours ?? $globalRule?->min_weekly_hours ?? self::SYSTEM_DEFAULTS['min_weekly_hours']),
            'min_rest_hours_between_shifts' => (float) ($deptRule?->min_rest_hours_between_shifts ?? $globalRule?->min_rest_hours_between_shifts ?? self::SYSTEM_DEFAULTS['min_rest_hours_between_shifts']),
            'max_consecutive_work_days'     => (int) ($deptRule?->max_consecutive_work_days ?? $globalRule?->max_consecutive_work_days ?? self::SYSTEM_DEFAULTS['max_consecutive_work_days']),
            'allow_night_shifts'            => (bool) ($deptRule?->allow_night_shifts ?? $globalRule?->allow_night_shifts ?? self::SYSTEM_DEFAULTS['allow_night_shifts']),
            'weekend_rotation_policy'       => $deptRule?->weekend_rotation_policy ?? $globalRule?->weekend_rotation_policy ?? self::SYSTEM_DEFAULTS['weekend_rotation_policy'],
        ];
    }

    /**
     * Lista todas las reglas configuradas para el tenant (global y departamentales).
     */
    public function listForCompany(int $companyId): Collection
    {
        return BusinessRule::with('department')
            ->where('company_id', $companyId)
            ->orderBy('department_scope_id')
            ->get();
    }

    /**
     * Crea o actualiza una regla de negocio corporativa o departamental.
     */
    public function createOrUpdate(array $data, User $actor): BusinessRule
    {
        $companyId    = (int) $actor->company_id;
        $departmentId = !empty($data['department_id']) ? (int) $data['department_id'] : null;
        $scopeId      = $departmentId ?: 0;

        if ($departmentId) {
            $dept = Department::where('company_id', $companyId)->find($departmentId);
            if (!$dept) {
                throw ValidationException::withMessages([
                    'department_id' => 'El departamento seleccionado no existe o pertenece a otra empresa.',
                ]);
            }
        }

        $existing = BusinessRule::where('company_id', $companyId)
            ->where('department_scope_id', $scopeId)
            ->first();

        $oldValues = $existing ? $existing->toArray() : [];

        $payload = [
            'company_id'                    => $companyId,
            'department_id'                 => $departmentId,
            'max_daily_hours'               => $data['max_daily_hours'] ?? null,
            'min_daily_hours'               => $data['min_daily_hours'] ?? null,
            'max_weekly_hours'              => $data['max_weekly_hours'] ?? null,
            'min_weekly_hours'              => $data['min_weekly_hours'] ?? null,
            'min_rest_hours_between_shifts' => $data['min_rest_hours_between_shifts'] ?? null,
            'max_consecutive_work_days'     => $data['max_consecutive_work_days'] ?? null,
            'allow_night_shifts'            => isset($data['allow_night_shifts']) ? (bool) $data['allow_night_shifts'] : null,
            'weekend_rotation_policy'       => $data['weekend_rotation_policy'] ?? null,
        ];

        // Filtrar nulos si se está creando la regla global para asegurar defaults
        if ($scopeId === 0 && !$existing) {
            foreach (self::SYSTEM_DEFAULTS as $key => $defaultVal) {
                if (!isset($payload[$key])) {
                    $payload[$key] = $defaultVal;
                }
            }
        }

        if ($existing) {
            $existing->update($payload);
            $rule = $existing->fresh(['department']);
            AuditService::log(
                AuditAction::UPDATE,
                BusinessRule::class,
                $rule->id,
                $scopeId === 0 ? 'Regla de negocio global actualizada' : "Regla de negocio para departamento {$rule->department?->name} actualizada",
                $oldValues,
                $rule->toArray(),
                $companyId
            );
        } else {
            $rule = BusinessRule::create($payload);
            $rule->load('department');
            AuditService::log(
                AuditAction::CREATE,
                BusinessRule::class,
                $rule->id,
                $scopeId === 0 ? 'Regla de negocio global creada' : "Regla de negocio para departamento {$rule->department?->name} creada",
                [],
                $rule->toArray(),
                $companyId
            );
        }

        return $rule;
    }

    /**
     * Elimina una regla departamental (no se permite eliminar la global si es la única).
     */
    public function delete(BusinessRule $rule, User $actor): void
    {
        if ($rule->company_id !== $actor->company_id) {
            throw ValidationException::withMessages([
                'business_rule' => 'Acceso denegado: La regla de negocio pertenece a otra empresa.',
            ]);
        }

        $oldValues = $rule->toArray();
        $desc = $rule->department_scope_id === 0
            ? 'Regla de negocio global eliminada'
            : "Regla de negocio para departamento {$rule->department?->name} eliminada";

        $rule->delete();

        AuditService::log(
            AuditAction::DELETE,
            BusinessRule::class,
            $rule->id,
            $desc,
            $oldValues,
            [],
            $actor->company_id
        );
    }
}
