export type DocumentType = 'CC' | 'CE' | 'TI' | 'PASSPORT' | 'OTHER' | 'NIT';

export type EmployeeStatus = 'ACTIVE' | 'INACTIVE' | 'ON_LEAVE' | 'TERMINATED';

export interface EmployeeItem {
  id: number;
  company_id: number;
  employee_code: string;
  document_type: DocumentType;
  document_number: string;
  first_name: string;
  middle_name?: string | null;
  last_name: string;
  second_last_name?: string | null;
  full_name: string;
  email: string;
  personal_email?: string | null;
  phone?: string | null;
  birth_date?: string | null;
  hire_date: string;
  termination_date?: string | null;
  department_id: number;
  position_id: number;
  employment_type_id: number;
  supervisor_id?: number | null;
  status: EmployeeStatus;
  notes?: string | null;

  department?: {
    id: number;
    name: string;
    code: string;
    cost_center_code?: string | null;
  } | null;

  position?: {
    id: number;
    name: string;
    code: string;
  } | null;

  employment_type?: {
    id: number;
    name: string;
    code: string;
    default_weekly_hours: number;
  } | null;

  supervisor?: {
    id: number;
    employee_code: string;
    full_name: string;
    email: string;
  } | null;

  user?: {
    id: number;
    username: string;
    email: string;
    status: string;
    role?: {
      id: number;
      code: string;
      name: string;
    } | null;
  } | null;

  created_at?: string;
  updated_at?: string;
}

export interface CreateEmployeePayload {
  employee_code: string;
  document_type: DocumentType;
  document_number: string;
  first_name: string;
  middle_name?: string | null;
  last_name: string;
  second_last_name?: string | null;
  email: string;
  personal_email?: string | null;
  phone?: string | null;
  birth_date?: string | null;
  hire_date: string;
  termination_date?: string | null;
  department_id: number;
  position_id: number;
  employment_type_id: number;
  supervisor_id?: number | null;
  status?: EmployeeStatus;
  notes?: string | null;
}

export interface UpdateEmployeePayload {
  employee_code?: string;
  document_type?: DocumentType;
  document_number?: string;
  first_name?: string;
  middle_name?: string | null;
  last_name?: string;
  second_last_name?: string | null;
  email?: string;
  personal_email?: string | null;
  phone?: string | null;
  birth_date?: string | null;
  hire_date?: string;
  termination_date?: string | null;
  department_id?: number;
  position_id?: number;
  employment_type_id?: number;
  supervisor_id?: number | null;
  status?: EmployeeStatus;
  notes?: string | null;
}

export interface EmployeeFilters {
  search?: string;
  department_id?: number | string;
  position_id?: number | string;
  employment_type_id?: number | string;
  status?: string;
  page?: number;
  per_page?: number;
  sort_by?: string;
  sort_order?: 'asc' | 'desc';
}

export interface CompactEmployeeOption {
  id: number;
  employee_code: string;
  first_name: string;
  last_name: string;
  email: string;
}
