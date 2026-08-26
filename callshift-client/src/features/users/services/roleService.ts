import apiClient from '@/lib/axios';
import type { ApiResponse } from '@/types/api.types';
import type { RoleItem, PermissionItem } from '@/types/role.types';

export const roleService = {
  /**
   * Obtiene todos los roles disponibles para el tenant.
   */
  async getRoles(): Promise<RoleItem[]> {
    const response = await apiClient.get<ApiResponse<RoleItem[]>>('/roles');
    return response.data.data;
  },

  /**
   * Obtiene un rol por ID con sus permisos.
   */
  async getRole(id: number): Promise<RoleItem> {
    const response = await apiClient.get<ApiResponse<RoleItem>>(`/roles/${id}`);
    return response.data.data;
  },

  /**
   * Obtiene la lista completa de permisos del sistema.
   */
  async getPermissions(): Promise<PermissionItem[]> {
    const response = await apiClient.get<ApiResponse<PermissionItem[]>>('/permissions');
    return response.data.data;
  },
};
