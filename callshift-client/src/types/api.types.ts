/**
 * Estándar unificado de respuesta de API para CallShift HR.
 */
export interface ApiResponse<T = unknown> {
  success: boolean;
  message: string;
  data: T;
  errors?: Record<string, string[]>;
}

export interface PaginatedMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number;
  to?: number;
}

export interface PaginatedResponse<T> {
  success: boolean;
  message: string;
  data: T[];
  meta?: PaginatedMeta;
  links?: {
    first?: string;
    last?: string;
    prev?: string | null;
    next?: string | null;
  };
}

export interface PaginatedData<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number;
  to: number;
}

export type RoleCode = 
  | 'SUPER_ADMIN'
  | 'HR_ADMIN'
  | 'MANAGER'
  | 'SUPERVISOR'
  | 'EMPLOYEE'
  | 'VIEWER';

export type EmployeeStatus = 
  | 'ACTIVE'
  | 'INACTIVE'
  | 'ON_LEAVE'
  | 'TERMINATED';

export type DayType = 
  | 'WORK'
  | 'REST'
  | 'OFF'
  | 'HOLIDAY'
  | 'PERMISSION'
  | 'ABSENCE';

export type WorkPeriodStatus = 
  | 'DRAFT'
  | 'GENERATED'
  | 'REVIEW'
  | 'PUBLISHED'
  | 'CLOSED';

export type ScheduleVersionStatus = 
  | 'DRAFT'
  | 'REVIEW'
  | 'PUBLISHED'
  | 'ARCHIVED';

export type AbsenceType = 
  | 'SICK_LEAVE'
  | 'VACATION'
  | 'PERMISSION'
  | 'BEREAVEMENT'
  | 'UNEXCUSED'
  | 'MATERNITY_PATERNITY'
  | 'OTHER';
