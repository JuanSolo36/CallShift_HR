export interface EmploymentTypeItem {
  id: number;
  company_id: number;
  name: string;
  code: string;
  default_weekly_hours: number;
  description?: string | null;
  status: 'ACTIVE' | 'INACTIVE';
  employees_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface CreateEmploymentTypePayload {
  name: string;
  code: string;
  default_weekly_hours: number;
  description?: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface UpdateEmploymentTypePayload {
  name?: string;
  code?: string;
  default_weekly_hours?: number;
  description?: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface EmploymentTypeFilters {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}
