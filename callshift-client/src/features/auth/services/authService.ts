import apiClient from '@/lib/axios';
import type { ApiResponse } from '@/types/api.types';
import type { AuthResponseData, UserSession } from '@/types/auth.types';

export interface LoginPayload {
  login: string;
  password: string;
  device_name?: string;
}

export interface ChangePasswordPayload {
  current_password: string;
  password: string;
  password_confirmation: string;
  revoke_other_sessions?: boolean;
}

export const authService = {
  /**
   * Inicia sesión contra la API REST v1.
   */
  async login(payload: LoginPayload): Promise<AuthResponseData> {
    const response = await apiClient.post<ApiResponse<AuthResponseData>>('/auth/login', payload);
    return response.data.data;
  },

  /**
   * Obtiene la sesión del usuario actual autenticado.
   */
  async getMe(): Promise<UserSession> {
    const response = await apiClient.get<ApiResponse<UserSession>>('/auth/me');
    return response.data.data;
  },

  /**
   * Cierra la sesión activa revocando el token Sanctum.
   */
  async logout(): Promise<void> {
    await apiClient.post('/auth/logout');
  },

  /**
   * Actualiza la contraseña del usuario.
   */
  async changePassword(payload: ChangePasswordPayload): Promise<void> {
    await apiClient.put('/auth/password', payload);
  },
};
