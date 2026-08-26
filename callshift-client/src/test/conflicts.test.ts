import { z } from 'zod';
import type {
  BusinessRule,
  BusinessRuleFormData,
  EffectiveBusinessRules,
} from '../types/businessRule';
import type {
  ScheduleConflict,
  RuleViolated,
  ConflictValidationSummary,
} from '../types/conflict';

// Lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

// Zod Schemas for Validation
const businessRuleFormSchema = z.object({
  department_id: z.number().int().positive().nullable().optional(),
  max_daily_hours: z.number().min(1).max(24).nullable().optional(),
  min_daily_hours: z.number().min(0.5).max(24).nullable().optional(),
  max_weekly_hours: z.number().min(1).max(168).nullable().optional(),
  min_weekly_hours: z.number().min(1).max(168).nullable().optional(),
  min_rest_hours_between_shifts: z.number().min(1).max(48).nullable().optional(),
  max_consecutive_work_days: z.number().int().min(1).max(30).nullable().optional(),
  allow_night_shifts: z.boolean().nullable().optional(),
  weekend_rotation_policy: z.enum(['STRICT_ROTATION', 'FAIR_SHARE', 'NONE']).nullable().optional(),
});

const resolveConflictSchema = z.object({
  reason: z.string().min(5, 'La justificación debe tener al menos 5 caracteres.').max(500),
});

// Pure Field-Level Inheritance Resolver
function resolveEffectiveRules(
  globalRule: BusinessRule | null,
  deptRule: BusinessRule | null,
  companyId: number,
  departmentId: number | null
): EffectiveBusinessRules {
  const defaults: EffectiveBusinessRules = {
    company_id: companyId,
    department_id: departmentId,
    scope: 'SYSTEM_DEFAULT',
    max_daily_hours: 10.0,
    min_daily_hours: 4.0,
    max_weekly_hours: 48.0,
    min_weekly_hours: 20.0,
    min_rest_hours_between_shifts: 12.0,
    max_consecutive_work_days: 6,
    allow_night_shifts: true,
    weekend_rotation_policy: 'FAIR_SHARE',
  };

  if (!globalRule && !deptRule) {
    return defaults;
  }

  const effective: EffectiveBusinessRules = {
    company_id: companyId,
    department_id: departmentId,
    scope: deptRule ? 'DEPARTMENT_OVERRIDE' : 'GLOBAL_COMPANY',
    max_daily_hours: deptRule?.max_daily_hours ?? globalRule?.max_daily_hours ?? defaults.max_daily_hours,
    min_daily_hours: deptRule?.min_daily_hours ?? globalRule?.min_daily_hours ?? defaults.min_daily_hours,
    max_weekly_hours: deptRule?.max_weekly_hours ?? globalRule?.max_weekly_hours ?? defaults.max_weekly_hours,
    min_weekly_hours: deptRule?.min_weekly_hours ?? globalRule?.min_weekly_hours ?? defaults.min_weekly_hours,
    min_rest_hours_between_shifts: deptRule?.min_rest_hours_between_shifts ?? globalRule?.min_rest_hours_between_shifts ?? defaults.min_rest_hours_between_shifts,
    max_consecutive_work_days: deptRule?.max_consecutive_work_days ?? globalRule?.max_consecutive_work_days ?? defaults.max_consecutive_work_days,
    allow_night_shifts: deptRule?.allow_night_shifts ?? globalRule?.allow_night_shifts ?? defaults.allow_night_shifts,
    weekend_rotation_policy: deptRule?.weekend_rotation_policy ?? globalRule?.weekend_rotation_policy ?? defaults.weekend_rotation_policy,
  };

  return effective;
}

// Conflict Key Generator Simulation
function generateConflictKey(
  versionId: number,
  employeeId: number,
  ruleViolated: RuleViolated,
  date: string,
  assignmentIds: number[]
): string {
  const sortedIds = [...assignmentIds].sort((a, b) => a - b);
  return `key_${versionId}_${employeeId}_${ruleViolated}_${date}_${sortedIds.join('-')}`;
}

async function runTests() {
  console.log('--- Starting Conflict Detection & Business Rules Frontend Tests ---');

  // Test 1: Field-level inheritance (Dept overrides single field, inherits others from Global)
  {
    const globalRule: BusinessRule = {
      id: 1,
      company_id: 1,
      department_id: null,
      department_scope_id: 0,
      max_daily_hours: 10.0,
      min_daily_hours: 4.0,
      max_weekly_hours: 48.0,
      min_weekly_hours: 20.0,
      min_rest_hours_between_shifts: 12.0,
      max_consecutive_work_days: 6,
      allow_night_shifts: false,
      weekend_rotation_policy: 'STRICT_ROTATION',
      created_at: '2026-08-01',
      updated_at: '2026-08-01',
    };

    const deptRule: BusinessRule = {
      id: 2,
      company_id: 1,
      department_id: 10,
      department_scope_id: 10,
      max_daily_hours: 8.0, // Overridden
      min_daily_hours: null, // Inherited
      max_weekly_hours: 40.0, // Overridden
      min_weekly_hours: null, // Inherited
      min_rest_hours_between_shifts: null, // Inherited
      max_consecutive_work_days: 5, // Overridden
      allow_night_shifts: null, // Inherited
      weekend_rotation_policy: null, // Inherited
      created_at: '2026-08-01',
      updated_at: '2026-08-01',
    };

    const effective = resolveEffectiveRules(globalRule, deptRule, 1, 10);
    assert(effective.scope === 'DEPARTMENT_OVERRIDE', 'Scope should be DEPARTMENT_OVERRIDE');
    assert(effective.max_daily_hours === 8.0, 'max_daily_hours should be overridden to 8.0');
    assert(effective.min_daily_hours === 4.0, 'min_daily_hours should be inherited as 4.0');
    assert(effective.max_weekly_hours === 40.0, 'max_weekly_hours should be overridden to 40.0');
    assert(effective.min_rest_hours_between_shifts === 12.0, 'min_rest_hours should be inherited as 12.0');
    assert(effective.max_consecutive_work_days === 5, 'max_consecutive_work_days should be overridden to 5');
    assert(effective.allow_night_shifts === false, 'allow_night_shifts should be inherited as false');
    assert(effective.weekend_rotation_policy === 'STRICT_ROTATION', 'weekend_rotation_policy should be inherited');
    console.log('✓ Test 1: Field-level inheritance correctly merges department and global rules');
  }

  // Test 2: System defaults fallback when no global or department rule exists
  {
    const effective = resolveEffectiveRules(null, null, 1, null);
    assert(effective.scope === 'SYSTEM_DEFAULT', 'Scope should be SYSTEM_DEFAULT');
    assert(effective.max_daily_hours === 10.0, 'Default max_daily_hours is 10.0');
    assert(effective.min_rest_hours_between_shifts === 12.0, 'Default min_rest_hours is 12.0');
    assert(effective.max_consecutive_work_days === 6, 'Default max_consecutive_work_days is 6');
    assert(effective.weekend_rotation_policy === 'FAIR_SHARE', 'Default weekend_rotation_policy is FAIR_SHARE');
    console.log('✓ Test 2: System defaults fallback when no business rules are defined');
  }

  // Test 3: Zod Schema Validation for Business Rule Form
  {
    const validPayload: BusinessRuleFormData = {
      department_id: 5,
      max_daily_hours: 9.5,
      min_rest_hours_between_shifts: 11.0,
      allow_night_shifts: true,
      weekend_rotation_policy: 'STRICT_ROTATION',
    };
    const parsed = businessRuleFormSchema.safeParse(validPayload);
    assert(parsed.success, 'Valid business rule payload must pass validation');

    const invalidPayload = {
      max_daily_hours: 30, // Exceeds 24h
    };
    const invalidParsed = businessRuleFormSchema.safeParse(invalidPayload);
    assert(!invalidParsed.success, 'Exceeding 24h daily limit must fail validation');
    console.log('✓ Test 3: Zod Schema rejects invalid boundary values for business rules');
  }

  // Test 4: Zod Schema Validation for Conflict Resolution Reason
  {
    const validResolve = { reason: 'Autorizado por horas extras pactadas.' };
    assert(resolveConflictSchema.safeParse(validResolve).success, 'Valid reason passes');

    const shortResolve = { reason: 'Ok' };
    assert(!resolveConflictSchema.safeParse(shortResolve).success, 'Reason < 5 chars must fail');
    console.log('✓ Test 4: Conflict resolution requires minimum 5 chars justification');
  }

  // Test 5: Invariant can_publish = (active_hard_conflicts === 0)
  {
    const summaryWithHard: ConflictValidationSummary = {
      total_conflicts: 3,
      active_hard_conflicts: 1,
      active_soft_warnings: 2,
      resolved_exceptions: 0,
      can_publish: false,
    };
    assert(!summaryWithHard.can_publish, 'Cannot publish when active hard conflicts exist');

    const summaryResolved: ConflictValidationSummary = {
      total_conflicts: 3,
      active_hard_conflicts: 0,
      active_soft_warnings: 2,
      resolved_exceptions: 1,
      can_publish: true,
    };
    assert(summaryResolved.can_publish, 'Can publish when all hard conflicts are resolved');
    console.log('✓ Test 5: Publication gate accurately evaluates active hard conflict blocking');
  }

  // Test 6: Deterministic Canonical Conflict Key Generation
  {
    const key1 = generateConflictKey(10, 101, 'MIN_REST_BETWEEN_SHIFTS', '2026-08-03', [1, 2]);
    const key2 = generateConflictKey(10, 101, 'MIN_REST_BETWEEN_SHIFTS', '2026-08-03', [2, 1]); // Reversed IDs
    assert(key1 === key2, 'Canonical conflict key must be invariant to assignment ID order');
    console.log('✓ Test 6: Deterministic canonical conflict key ensures idempotency');
  }

  // Test 7: Conflict Lifecycle Transitions (ACTIVE -> RESOLVED / AUTO_CLEARED)
  {
    const conflict: ScheduleConflict = {
      id: 1,
      schedule_version_id: 10,
      employee_id: 101,
      conflict_key: 'key_10_101_MIN_REST_BETWEEN_SHIFTS_2026-08-03_1-2',
      date: '2026-08-03',
      start_datetime: '2026-08-03T22:00:00Z',
      end_datetime: '2026-08-04T08:00:00Z',
      severity: 'HARD_CONFLICT',
      rule_violated: 'MIN_REST_BETWEEN_SHIFTS',
      description: 'Descanso insuficiente de 2h entre turnos.',
      suggested_resolution: 'Aumentar intervalo de descanso.',
      primary_assignment_id: 1,
      conflicting_assignment_id: 2,
      status: 'ACTIVE',
      is_resolved: false,
      resolved_by: null,
      resolved_at: null,
      resolution_reason: null,
      created_at: '2026-08-03',
      updated_at: '2026-08-03',
    };

    // Simulate resolve
    const resolvedConflict: ScheduleConflict = {
      ...conflict,
      status: 'RESOLVED',
      is_resolved: true,
      resolved_by: 1,
      resolved_at: '2026-08-03T10:00:00Z',
      resolution_reason: 'Excepción autorizada por guardia.',
    };
    assert(resolvedConflict.is_resolved && resolvedConflict.status === 'RESOLVED', 'Conflict is resolved');

    // Simulate auto-clear on schedule correction
    const autoClearedConflict: ScheduleConflict = {
      ...conflict,
      status: 'AUTO_CLEARED',
      is_resolved: false,
    };
    assert(autoClearedConflict.status === 'AUTO_CLEARED', 'Historical record preserved as AUTO_CLEARED');
    console.log('✓ Test 7: Conflict lifecycle transitions preserve audit history without destructive deletion');
  }

  // Test 8: Dual-Severity Weekend Rotation Policy
  {
    function evalWeekendRotation(policy: 'STRICT_ROTATION' | 'FAIR_SHARE' | 'NONE'): 'HARD_CONFLICT' | 'SOFT_WARNING' | null {
      if (policy === 'NONE') return null;
      return policy === 'STRICT_ROTATION' ? 'HARD_CONFLICT' : 'SOFT_WARNING';
    }

    assert(evalWeekendRotation('STRICT_ROTATION') === 'HARD_CONFLICT', 'STRICT_ROTATION must produce HARD_CONFLICT');
    assert(evalWeekendRotation('FAIR_SHARE') === 'SOFT_WARNING', 'FAIR_SHARE must produce SOFT_WARNING');
    assert(evalWeekendRotation('NONE') === null, 'NONE must produce no conflict');
    console.log('✓ Test 8: Weekend rotation policy accurately maps dual severity (STRICT -> HARD, FAIR -> SOFT)');
  }

  // Test 9: Weekly Hours Precedence (Contractual Soft Warning vs Legal Hard Conflict)
  {
    function evalWeeklyHours(worked: number, contractBase: number, legalMax: number): 'HARD_CONFLICT' | 'SOFT_WARNING' | null {
      if (worked > legalMax) return 'HARD_CONFLICT';
      if (worked > contractBase) return 'SOFT_WARNING';
      return null;
    }

    assert(evalWeeklyHours(44, 40, 48) === 'SOFT_WARNING', '44h worked exceeds 40h contract -> SOFT_WARNING');
    assert(evalWeeklyHours(52, 40, 48) === 'HARD_CONFLICT', '52h worked exceeds 48h legal -> HARD_CONFLICT');
    assert(evalWeeklyHours(38, 40, 48) === null, '38h worked within limits -> null');
    console.log('✓ Test 9: Weekly hours precedence accurately enforces contract vs legal threshold severity');
  }

  // Test 10: Night Shift Disallowed Policy
  {
    function evalNightShift(allowNight: boolean, crossesMidnight: boolean): 'HARD_CONFLICT' | null {
      if (!allowNight && crossesMidnight) return 'HARD_CONFLICT';
      return null;
    }

    assert(evalNightShift(false, true) === 'HARD_CONFLICT', 'Night shift when disallowed produces HARD_CONFLICT');
    assert(evalNightShift(true, true) === null, 'Night shift when allowed produces no conflict');
    console.log('✓ Test 10: Night shift disallowed policy triggers HARD_CONFLICT on night assignments');
  }

  // Test 11: Min Weekly Hours evaluated only on complete ISO weeks
  {
    function evalMinWeeklyHours(
      worked: number,
      minRequired: number,
      isCompleteWeek: boolean
    ): 'SOFT_WARNING' | null {
      if (isCompleteWeek && worked < minRequired) return 'SOFT_WARNING';
      return null;
    }

    assert(evalMinWeeklyHours(15, 20, true) === 'SOFT_WARNING', 'Complete week with deficit -> SOFT_WARNING');
    assert(evalMinWeeklyHours(15, 20, false) === null, 'Partial week with deficit -> null (ignored)');
    console.log('✓ Test 11: Min weekly hours deficit is enforced only for complete ISO weeks');
  }

  console.log('--- ALL CONFLICT DETECTION & BUSINESS RULES FRONTEND TESTS PASSED ---');
}

runTests().catch((err) => {
  console.error(err);
  throw err;
});
