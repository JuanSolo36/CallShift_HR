import apiClient from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type { UserItem, CreateUserPayload, UpdateUserPayload, UserFilters } from '@/types/user.types';

export const userService = {
  /**
   * Obtiene el listado paginado y filtrado de usuarios.
   */
  async getUsers(filters: UserFilters = {}): Promise<PaginatedResponse<UserItem>> {
    const response = await apiClient.get<PaginatedResponse<UserItem>>('/users', {
      params: filters,
    });
    return response.data;
  },

  /**
   * Consulta el detalle de un usuario por ID.
   */
  async getUser(id: number): Promise<UserItem> {
    const response = await apiClient.get<ApiResponse<UserItem>>(`/users/${id}`);
    return response.data.data;
  },

  /**
   * Crea un nuevo usuario.
   */
  async createUser(payload: CreateUserPayload): Promise<UserItem> {
    const response = await apiClient.post<ApiResponse<UserItem>>('/users', payload);
    return response.data.data;
  },

  /**
   * Actualiza los datos de un usuario existente.
   */
  async updateUser(id: number, payload: UpdateUserPayload): Promise<UserItem> {
    const response = await apiClient.put<ApiResponse<UserItem>>(`/users/${id}`, payload);
    return response.data.data;
  },

  /**
   * Cambia el estado de un usuario (ACTIVE, INACTIVE, SUSPENDED).
   */
  async changeStatus(id: number, status: string, reason?: string): Promise<UserItem> {
    const response = await apiClient.patch<ApiResponse<UserItem>>(`/users/${id}/status`, {
      status,
      reason,
    });
    return response.data.data;
  },

  /**
   * Elimina lógicamente un usuario.
   */
  async deleteUser(id: number): Promise<void> {
    await apiClient.delete(`/users/${id}`);
  },
};
