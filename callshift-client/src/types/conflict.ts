export type ConflictSeverity = 'HARD_CONFLICT' | 'SOFT_WARNING';

export type ConflictStatus = 'ACTIVE' | 'RESOLVED' | 'AUTO_CLEARED';

export type RuleViolated =
  | 'OVERLAPPING_SHIFTS'
  | 'MIN_REST_BETWEEN_SHIFTS'
  | 'MAX_CONSECUTIVE_WORK_DAYS'
  | 'MAX_DAILY_HOURS'
  | 'MIN_DAILY_HOURS'
  | 'LEGAL_WEEKLY_HOURS_EXCEEDED'
  | 'CONTRACT_WEEKLY_HOURS_EXCEEDED'
  | 'MIN_WEEKLY_HOURS'
  | 'APPROVED_ABSENCE_COLLISION'
  | 'UNAVAILABLE_RESTRICTION'
  | 'WEEKEND_ROTATION_VIOLATION'
  | 'NIGHT_SHIFT_DISALLOWED';

export interface ScheduleConflict {
  id: number;
  schedule_version_id: number;
  employee_id: number;
  employee?: {
    id: number;
    first_name: string;
    last_name: string;
    employee_code: string;
    department?: {
      id: number;
      name: string;
    } | null;
  } | null;
  conflict_key: string;
  date: string;
  start_datetime: string | null;
  end_datetime: string | null;
  severity: ConflictSeverity;
  rule_violated: RuleViolated;
  description: string;
  suggested_resolution: string | null;
  primary_assignment_id: number | null;
  conflicting_assignment_id: number | null;
  status: ConflictStatus;
  is_resolved: boolean;
  resolved_by: number | null;
  resolved_at: string | null;
  resolution_reason: string | null;
  resolver?: {
    id: number;
    name: string;
    email: string;
  } | null;
  created_at: string;
  updated_at: string;
}

export interface ConflictValidationSummary {
  total_conflicts: number;
  active_hard_conflicts: number;
  active_soft_warnings: number;
  resolved_exceptions: number;
  can_publish: boolean;
}

export interface ConflictValidationResponse {
  success: boolean;
  message: string;
  summary: ConflictValidationSummary;
  data: ScheduleConflict[];
}
