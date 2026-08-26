import { apiClient } from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type {
  WorkPeriodItem,
  CreateWorkPeriodPayload,
  UpdateWorkPeriodPayload,
  ChangeWorkPeriodStatusPayload,
  WorkPeriodFilters,
} from '@/types/workPeriod.types';

export const workPeriodService = {
  async getWorkPeriods(filters: WorkPeriodFilters = {}): Promise<PaginatedResponse<WorkPeriodItem>> {
    const response = await apiClient.get<PaginatedResponse<WorkPeriodItem>>('/work-periods', {
      params: filters,
    });
    return response.data;
  },

  async getWorkPeriodsCompact(filters: Partial<WorkPeriodFilters> = {}): Promise<WorkPeriodItem[]> {
    const response = await apiClient.get<ApiResponse<WorkPeriodItem[]>>('/work-periods/compact', {
      params: filters,
    });
    return response.data.data;
  },

  async getWorkPeriodById(id: number): Promise<WorkPeriodItem> {
    const response = await apiClient.get<ApiResponse<WorkPeriodItem>>(`/work-periods/${id}`);
    return response.data.data;
  },

  async createWorkPeriod(payload: CreateWorkPeriodPayload): Promise<WorkPeriodItem> {
    const response = await apiClient.post<ApiResponse<WorkPeriodItem>>('/work-periods', payload);
    return response.data.data;
  },

  async updateWorkPeriod(id: number, payload: UpdateWorkPeriodPayload): Promise<WorkPeriodItem> {
    const response = await apiClient.put<ApiResponse<WorkPeriodItem>>(`/work-periods/${id}`, payload);
    return response.data.data;
  },

  async changeWorkPeriodStatus(id: number, payload: ChangeWorkPeriodStatusPayload): Promise<WorkPeriodItem> {
    const response = await apiClient.patch<ApiResponse<WorkPeriodItem>>(`/work-periods/${id}/status`, payload);
    return response.data.data;
  },

  async deleteWorkPeriod(id: number): Promise<void> {
    await apiClient.delete(`/work-periods/${id}`);
  },
};
