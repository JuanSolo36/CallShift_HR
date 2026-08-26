export type AuditAction =
  | 'LOGIN'
  | 'LOGOUT'
  | 'CREATE'
  | 'UPDATE'
  | 'DELETE'
  | 'GENERATE'
  | 'PUBLISH'
  | 'MODIFY'
  | 'EXPORT'
  | 'RESTORE';

export interface AuditLogUser {
  id: number;
  username: string;
  email: string;
  first_name?: string | null;
  last_name?: string | null;
}

export interface AuditLogItem {
  id: number;
  company_id: number;
  user_id: number | null;
  user: AuditLogUser | null;
  action: AuditAction;
  action_label: string;
  auditable_type: string;
  auditable_type_name: string;
  auditable_id: number | null;
  description: string;
  old_values: Record<string, any> | null;
  new_values: Record<string, any> | null;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
}

export interface AuditFilters {
  user_id?: number;
  action?: AuditAction;
  auditable_type?: string;
  auditable_id?: number;
  date_from?: string;
  date_to?: string;
  search?: string;
  page?: number;
  per_page?: number;
}
