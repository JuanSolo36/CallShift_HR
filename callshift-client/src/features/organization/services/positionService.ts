import { apiClient } from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type {
  PositionItem,
  CreatePositionPayload,
  UpdatePositionPayload,
  PositionFilters,
  CompactOption,
} from '@/types/organization.types';

export const positionService = {
  async getPositions(filters: PositionFilters = {}): Promise<PaginatedResponse<PositionItem>> {
    const response = await apiClient.get<PaginatedResponse<PositionItem>>('/positions', {
      params: filters,
    });
    return response.data;
  },

  async getPositionsCompact(departmentId?: number): Promise<CompactOption[]> {
    const response = await apiClient.get<ApiResponse<CompactOption[]>>('/positions/compact', {
      params: departmentId ? { department_id: departmentId } : {},
    });
    return response.data.data;
  },

  async getPositionById(id: number): Promise<PositionItem> {
    const response = await apiClient.get<ApiResponse<PositionItem>>(`/positions/${id}`);
    return response.data.data;
  },

  async createPosition(payload: CreatePositionPayload): Promise<PositionItem> {
    const response = await apiClient.post<ApiResponse<PositionItem>>('/positions', payload);
    return response.data.data;
  },

  async updatePosition(id: number, payload: UpdatePositionPayload): Promise<PositionItem> {
    const response = await apiClient.put<ApiResponse<PositionItem>>(`/positions/${id}`, payload);
    return response.data.data;
  },

  async deletePosition(id: number): Promise<void> {
    await apiClient.delete(`/positions/${id}`);
  },
};
