export type WeekendRotationPolicy = 'STRICT_ROTATION' | 'FAIR_SHARE' | 'NONE';

export interface BusinessRule {
  id: number;
  company_id: number;
  department_id: number | null;
  department_scope_id: number;
  department?: {
    id: number;
    name: string;
    code: string;
  } | null;
  max_daily_hours: number | null;
  min_daily_hours: number | null;
  max_weekly_hours: number | null;
  min_weekly_hours: number | null;
  min_rest_hours_between_shifts: number | null;
  max_consecutive_work_days: number | null;
  allow_night_shifts: boolean | null;
  weekend_rotation_policy: WeekendRotationPolicy | null;
  created_at: string;
  updated_at: string;
}

export interface EffectiveBusinessRules {
  company_id: number;
  department_id: number | null;
  scope: 'DEPARTMENT_OVERRIDE' | 'GLOBAL_COMPANY' | 'SYSTEM_DEFAULT';
  max_daily_hours: number;
  min_daily_hours: number;
  max_weekly_hours: number;
  min_weekly_hours: number;
  min_rest_hours_between_shifts: number;
  max_consecutive_work_days: number;
  allow_night_shifts: boolean;
  weekend_rotation_policy: WeekendRotationPolicy;
}

export interface BusinessRuleFormData {
  department_id?: number | null;
  max_daily_hours?: number | null;
  min_daily_hours?: number | null;
  max_weekly_hours?: number | null;
  min_weekly_hours?: number | null;
  min_rest_hours_between_shifts?: number | null;
  max_consecutive_work_days?: number | null;
  allow_night_shifts?: boolean | null;
  weekend_rotation_policy?: WeekendRotationPolicy | null;
}
