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

export interface UserSession {
  id: number;
  company_id: number;
  employee_id?: number | null;
  username: string;
  email: string;
  status: 'ACTIVE' | 'INACTIVE' | 'SUSPENDED';
  last_login_at?: string | null;
  role: {
    id: number;
    code: RoleCode;
    name: string;
    description?: string | null;
  } | null;
  permissions: string[];
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

export interface AuthResponseData {
  token: string;
  token_type: string;
  user: UserSession;
}
