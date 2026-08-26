import type { RoleCode } from './auth.types';

export interface UserItem {
  id: number;
  company_id: number;
  employee_id?: number | null;
  username: string;
  email: string;
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
  last_login_at?: string | null;
  created_at: string;
  role: {
    id: number;
    code: RoleCode;
    name: string;
    description?: string | null;
  } | null;
  employee?: {
    id: number;
    employee_code: string;
    full_name: string;
    first_name: string;
    last_name: string;
    email: string;
    department?: {
      id: number;
      name: string;
      code: string;
    } | null;
    position?: {
      id: number;
      name: string;
      code: string;
    } | null;
  } | null;
  company?: {
    id: number;
    name: string;
    timezone: string;
    country: string;
  } | null;
}

export interface CreateUserPayload {
  username: string;
  email: string;
  password: string;
  password_confirmation: string;
  role_id: number;
  employee_id?: number | null;
  status?: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
}

export interface UpdateUserPayload {
  username?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
  role_id?: number;
  employee_id?: number | null;
  status?: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
}

export interface UserFilters {
  search?: string;
  status?: string;
  role_id?: number | string;
  department_id?: number | string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}
