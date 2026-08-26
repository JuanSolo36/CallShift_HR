export type ReportType =
  | 'employees'
  | 'schedules'
  | 'hours'
  | 'absences'
  | 'modifications'
  | 'audit';

export interface ReportMeta {
  current_page: number;
  from: number | null;
  last_page: number;
  per_page: number;
  to: number | null;
  total: number;
}

export interface EmployeeReportItem {
  id: number;
  employee_code: string;
  full_name: string;
  document_type: string;
  document_number: string;
  email: string;
  department?: { id: number; name: string; code: string } | null;
  position?: { id: number; name: string; code: string } | null;
  employment_type?: { id: number; name: string; code: string } | null;
  supervisor?: { id: number; full_name: string; employee_code: string } | null;
  hire_date?: string | null;
  status: string;
}

export interface ScheduleReportItem {
  id: number;
  schedule_version_id: number;
  employee_id: number;
  date: string;
  day_type: string;
  shift_type_id: number | null;
  shift_type?: {
    id: number;
    name: string;
    code: string;
    color_hex?: string;
    start_time?: string;
    end_time?: string;
    total_work_hours?: number;
  } | null;
  start_time: string | null;
  end_time: string | null;
  total_hours: number;
  is_custom: boolean;
  notes: string | null;
}

export interface HoursEmployeeRow {
  employee_id: number;
  employee_code: string;
  employee_name: string;
  department: string;
  total_work_hours: number;
  total_work_days: number;
  total_rest_days: number;
  total_absence_days: number;
  average_hours_day: number;
}

export interface HoursReportSummary {
  total_employees: number;
  total_hours: number;
  average_per_staff: number;
}

export interface HoursReportData {
  summary: HoursReportSummary;
  employees: HoursEmployeeRow[];
}

export interface AbsenceReportItem {
  id: number;
  company_id: number;
  employee_id: number;
  employee?: {
    id: number;
    full_name: string;
    employee_code: string;
    department?: string;
  } | null;
  type: string;
  type_label?: string;
  start_date: string;
  end_date: string;
  start_time: string | null;
  end_time: string | null;
  is_full_day: boolean;
  reason: string | null;
  status: string;
  approver?: {
    id: number;
    username: string;
    email: string;
  } | null;
  approved_at: string | null;
}

export interface ModificationReportItem {
  id: number;
  schedule_version_id: number;
  employee_id: number;
  employee?: {
    id: number;
    full_name: string;
    employee_code: string;
    department?: string;
  } | null;
  modification_type: string;
  modification_type_label?: string;
  reason: string;
  creator?: {
    id: number;
    username: string;
    email: string;
  } | null;
  created_at: string;
  previous_data: Record<string, any> | null;
  new_data: Record<string, any> | null;
  evidences_count?: number;
}

export interface ReportFilters {
  department_id?: number;
  position_id?: number;
  employment_type_id?: number;
  status?: string;
  work_period_id?: number;
  schedule_version_id?: number;
  employee_id?: number;
  type?: string;
  modification_type?: string;
  day_type?: string;
  date_from?: string;
  date_to?: string;
  hire_date_from?: string;
  hire_date_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
}
