export interface ShiftTypeItem {
  id: number;
  company_id: number;
  name: string;
  code: string;
  color_hex: string;
  start_time: string;
  end_time: string;
  break_duration_minutes: number;
  total_work_hours: number;
  crosses_midnight: boolean;
  description?: string | null;
  status: 'ACTIVE' | 'INACTIVE';
  assignments_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface CreateShiftTypePayload {
  name: string;
  code: string;
  color_hex: string;
  start_time: string;
  end_time: string;
  break_duration_minutes?: number;
  total_work_hours?: number;
  crosses_midnight?: boolean;
  description?: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface UpdateShiftTypePayload {
  name?: string;
  code?: string;
  color_hex?: string;
  start_time?: string;
  end_time?: string;
  break_duration_minutes?: number;
  total_work_hours?: number;
  crosses_midnight?: boolean;
  description?: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface ShiftTypeFilters {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}
