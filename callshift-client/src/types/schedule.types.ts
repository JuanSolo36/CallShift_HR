export type ScheduleDayType = 'WORK' | 'REST' | 'OFF' | 'HOLIDAY' | 'PERMISSION' | 'ABSENCE';

export interface ScheduleGridDay {
  date: string;
  day_of_week: number;
  day_name: string;
  day_number: number;
  formatted: string;
  is_weekend: boolean;
}

export interface ScheduleGridEmployee {
  id: number;
  employee_code: string;
  full_name: string;
  first_name: string;
  last_name: string;
  department: string | null;
  position: string | null;
}

export interface ScheduleShiftTypeItem {
  id: number;
  name: string;
  code: string;
  color_hex: string;
  start_time: string;
  end_time: string;
  total_work_hours: number;
  crosses_midnight: boolean;
}

export interface ScheduleAssignmentItem {
  id: number;
  schedule_version_id: number;
  employee_id: number;
  date: string;
  day_type: ScheduleDayType;
  shift_type_id: number | null;
  shift_type: ScheduleShiftTypeItem | null;
  start_time: string | null;
  end_time: string | null;
  starts_at: string | null;
  ends_at: string | null;
  total_hours: number;
  is_custom: boolean;
  notes: string | null;
  created_at?: string;
  updated_at?: string;
}

export interface ScheduleGridData {
  work_period: {
    id: number;
    name: string;
    department_id: number | null;
    department: {
      id: number;
      name: string;
      code: string;
    } | null;
    start_date: string;
    end_date: string;
    duration_days: number;
    status: string;
  };
  version: {
    id: number;
    version_number: number;
    status: string;
    lock_version: number;
    score: number;
    hard_conflicts_count: number;
    soft_conflicts_count: number;
    is_editable: boolean;
  };
  days: ScheduleGridDay[];
  employees: ScheduleGridEmployee[];
  shift_types: ScheduleShiftTypeItem[];
  assignments: ScheduleAssignmentItem[];
}

export interface UpsertAssignmentPayload {
  employee_id: number;
  date: string;
  day_type?: ScheduleDayType;
  shift_type_id?: number | null;
  lock_version: number;
  notes?: string;
}

export interface UpsertAssignmentResponse {
  assignment: ScheduleAssignmentItem;
  lock_version: number;
}

export interface DeleteAssignmentPayload {
  lock_version: number;
}

export interface DeleteAssignmentResponse {
  lock_version: number;
}

export type ModificationType =
  | 'SHIFT_SWAP'
  | 'SHIFT_CHANGE'
  | 'TIME_CHANGE'
  | 'WORKDAY_CHANGE'
  | 'DAY_OFF_CHANGE'
  | 'REST_DAY_CHANGE'
  | 'LEAVE_PERMISSION'
  | 'ABSENCE_COVERAGE'
  | 'ABSENCE'
  | 'ADMINISTRATIVE_ADJUSTMENT'
  | 'OTHER';

export interface ModificationEvidenceItem {
  id: number;
  schedule_modification_id: number;
  original_name: string;
  mime_type: string;
  file_size_bytes: number;
  file_size_human: string;
  sha256_hash: string;
  is_image: boolean;
  is_pdf: boolean;
  download_url: string;
  uploaded_by?: {
    id: number;
    username: string;
    email: string;
  };
  created_at: string;
}

export interface ScheduleModificationItem {
  id: number;
  schedule_version_id: number;
  schedule_assignment_id: number;
  employee_id: number;
  employee?: {
    id: number;
    first_name: string;
    last_name: string;
    employee_code: string;
  };
  modification_type: ModificationType;
  modification_type_label?: string;
  previous_data: Record<string, any>;
  new_data: Record<string, any>;
  reason: string;
  created_by: number;
  creator?: {
    id: number;
    username: string;
    email: string;
  };
  approved_by?: number | null;
  evidences?: ModificationEvidenceItem[];
  evidences_count?: number;
  created_at: string;
  updated_at: string;
}

export interface CreateModificationPayload {
  schedule_assignment_id: number;
  employee_id: number;
  modification_type: ModificationType;
  reason: string;
  shift_type_id?: number | null;
  day_type?: ScheduleDayType;
  start_time?: string | null;
  end_time?: string | null;
  total_hours?: number;
  is_custom?: boolean;
  notes?: string | null;
  evidences?: File[];
}

