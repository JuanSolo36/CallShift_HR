export type WorkPeriodType = 'WEEKLY' | 'BIWEEKLY' | 'MONTHLY' | 'CUSTOM';

export type WorkPeriodStatus = 'DRAFT' | 'GENERATED' | 'REVIEW' | 'PUBLISHED' | 'CLOSED';

export interface WorkPeriodItem {
  id: number;
  company_id: number;
  department_id?: number | null;
  department?: {
    id: number;
    name: string;
    code: string;
  } | null;
  name: string;
  period_type: WorkPeriodType;
  start_date: string;
  end_date: string;
  duration_days: number;
  status: WorkPeriodStatus;
  status_label: string;
  current_version_id?: number | null;
  current_version?: {
    id: number;
    version_number: number;
    status: string;
    lock_version: number;
    score?: number | null;
    hard_conflicts_count: number;
    soft_conflicts_count: number;
  } | null;
  versions_count?: number;
  created_by: number;
  creator?: {
    id: number;
    username: string;
    email: string;
  } | null;
  created_at?: string;
  updated_at?: string;
}

export interface CreateWorkPeriodPayload {
  name: string;
  period_type?: WorkPeriodType;
  department_id?: number | null;
  start_date: string;
  end_date: string;
  status?: WorkPeriodStatus;
}

export interface UpdateWorkPeriodPayload {
  name?: string;
  period_type?: WorkPeriodType;
  department_id?: number | null;
  start_date?: string;
  end_date?: string;
  status?: WorkPeriodStatus;
  lock_version?: number;
}

export interface ChangeWorkPeriodStatusPayload {
  status: WorkPeriodStatus;
  reason?: string | null;
  lock_version?: number;
}

export interface WorkPeriodFilters {
  search?: string;
  status?: string;
  department_id?: number;
  start_date?: string;
  end_date?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}
