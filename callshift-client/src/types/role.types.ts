import type { RoleCode } from './auth.types';

export interface PermissionItem {
  id: number;
  module: string;
  code: string;
  name: string;
  description?: string | null;
}

export interface RoleItem {
  id: number;
  code: RoleCode;
  name: string;
  description?: string | null;
  is_system: boolean;
  permissions_count?: number;
  permissions?: PermissionItem[];
}
