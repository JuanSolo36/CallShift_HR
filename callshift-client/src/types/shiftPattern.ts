export interface ShiftPatternEntry {
  id?: number;
  shift_pattern_id?: number;
  day_number: number;
  day_type: 'WORK' | 'REST' | 'OFF' | 'HOLIDAY' | 'PERMISSION' | 'ABSENCE';
  shift_type_id?: number | null;
  shift_type?: {
    id: number;
    name: string;
    code: string;
    color_hex: string;
    start_time: string;
    end_time: string;
    total_work_hours: number;
    crosses_midnight: boolean;
  } | null;
  start_time_override?: string | null;
  end_time_override?: string | null;
  notes?: string | null;
}

export interface ShiftPattern {
  id: number;
  company_id: number;
  department_id?: number | null;
  department?: {
    id: number;
    name: string;
    code: string;
  } | null;
  position_id?: number | null;
  position?: {
    id: number;
    name: string;
    code: string;
  } | null;
  name: string;
  code: string;
  cycle_length_days: number;
  description?: string | null;
  status: 'ACTIVE' | 'INACTIVE';
  entries?: ShiftPatternEntry[];
  created_by?: number | null;
  created_at?: string;
  updated_at?: string;
}

export interface ShiftTemplate {
  id: number;
  company_id: number;
  department_id?: number | null;
  department?: {
    id: number;
    name: string;
    code: string;
  } | null;
  position_id?: number | null;
  position?: {
    id: number;
    name: string;
    code: string;
  } | null;
  shift_pattern_id?: number | null;
  pattern?: ShiftPattern | null;
  name: string;
  code: string;
  description?: string | null;
  status: 'ACTIVE' | 'INACTIVE';
  metadata?: Record<string, any> | null;
  created_by?: number | null;
  created_at?: string;
  updated_at?: string;
}

export interface PatternProjectionItem {
  employee_id: number;
  employee_name: string;
  date: string;
  day_number: number;
  day_type: string;
  shift_type_id?: number | null;
  shift_type_name?: string | null;
  shift_type_code?: string | null;
  color_hex?: string;
  start_time?: string | null;
  end_time?: string | null;
  starts_at?: string | null;
  ends_at?: string | null;
  total_hours: number;
  is_overwriting: boolean;
}

export interface PatternPreviewSummary {
  employees_count: number;
  total_days_in_period: number;
  total_assignments: number;
  new_assignments: number;
  overwritten_count: number;
  total_work_hours: number;
  total_work_days: number;
  total_rest_days: number;
  conflicts_count?: number;
}

export interface PatternPreviewResponse {
  pattern: {
    id: number;
    name: string;
    code: string;
    cycle_length_days: number;
  };
  version: {
    id: number;
    lock_version: number;
  };
  work_period: {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
  };
  summary: PatternPreviewSummary;
  conflicts: any[];
  projections: Record<number, PatternProjectionItem[]>;
}

export interface ApplyPatternPayload {
  pattern_id: number;
  employee_ids: number[];
  start_offset_day?: number;
  start_date?: string;
  end_date?: string;
  override_existing?: boolean;
  lock_version: number;
}

export interface ApplyPatternResult {
  success: boolean;
  message: string;
  lock_version: number;
  summary: PatternPreviewSummary;
  persisted_count: number;
}
