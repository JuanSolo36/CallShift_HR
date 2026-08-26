import { apiClient } from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type {
  ShiftTypeItem,
  CreateShiftTypePayload,
  UpdateShiftTypePayload,
  ShiftTypeFilters,
} from '@/types/shiftType.types';

export const shiftTypeService = {
  async getShiftTypes(filters: ShiftTypeFilters = {}): Promise<PaginatedResponse<ShiftTypeItem>> {
    const response = await apiClient.get<PaginatedResponse<ShiftTypeItem>>('/shift-types', {
      params: filters,
    });
    return response.data;
  },

  async getShiftTypesCompact(): Promise<ShiftTypeItem[]> {
    const response = await apiClient.get<ApiResponse<ShiftTypeItem[]>>('/shift-types/compact');
    return response.data.data;
  },

  async getShiftTypeById(id: number): Promise<ShiftTypeItem> {
    const response = await apiClient.get<ApiResponse<ShiftTypeItem>>(`/shift-types/${id}`);
    return response.data.data;
  },

  async createShiftType(payload: CreateShiftTypePayload): Promise<ShiftTypeItem> {
    const response = await apiClient.post<ApiResponse<ShiftTypeItem>>('/shift-types', payload);
    return response.data.data;
  },

  async updateShiftType(id: number, payload: UpdateShiftTypePayload): Promise<ShiftTypeItem> {
    const response = await apiClient.put<ApiResponse<ShiftTypeItem>>(`/shift-types/${id}`, payload);
    return response.data.data;
  },

  async deleteShiftType(id: number): Promise<void> {
    await apiClient.delete(`/shift-types/${id}`);
  },
};
