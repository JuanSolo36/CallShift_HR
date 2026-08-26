export interface DepartmentItem {
  id: number;
  company_id: number;
  name: string;
  code: string;
  cost_center_code?: string | null;
  description?: string | null;
  status: 'ACTIVE' | 'INACTIVE';
  manager_id?: number | null;
  manager?: {
    id: number;
    employee_code: string;
    full_name: string;
    email: string;
  } | null;
  positions_count?: number;
  employees_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface CreateDepartmentPayload {
  name: string;
  code: string;
  cost_center_code?: string | null;
  description?: string | null;
  manager_id?: number | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface UpdateDepartmentPayload {
  name?: string;
  code?: string;
  cost_center_code?: string | null;
  description?: string | null;
  manager_id?: number | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface DepartmentFilters {
  search?: string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export interface PositionItem {
  id: number;
  company_id: number;
  department_id?: number | null;
  name: string;
  code: string;
  description?: string | null;
  status: 'ACTIVE' | 'INACTIVE';
  department?: {
    id: number;
    name: string;
    code: string;
    cost_center_code?: string | null;
  } | null;
  employees_count?: number;
  created_at?: string;
  updated_at?: string;
}

export interface CreatePositionPayload {
  name: string;
  code: string;
  department_id?: number | null;
  description?: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface UpdatePositionPayload {
  name?: string;
  code?: string;
  department_id?: number | null;
  description?: string | null;
  status?: 'ACTIVE' | 'INACTIVE';
}

export interface PositionFilters {
  search?: string;
  department_id?: number | string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export interface CompactOption {
  id: number;
  name: string;
  code: string;
  cost_center_code?: string | null;
  department_id?: number | null;
}
