import { apiClient } from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type {
  EmploymentTypeItem,
  CreateEmploymentTypePayload,
  UpdateEmploymentTypePayload,
  EmploymentTypeFilters,
} from '@/types/employmentType.types';
import type { CompactOption } from '@/types/organization.types';

export const employmentTypeService = {
  async getEmploymentTypes(filters: EmploymentTypeFilters = {}): Promise<PaginatedResponse<EmploymentTypeItem>> {
    const response = await apiClient.get<PaginatedResponse<EmploymentTypeItem>>('/employment-types', {
      params: filters,
    });
    return response.data;
  },

  async getEmploymentTypesCompact(): Promise<CompactOption[]> {
    const response = await apiClient.get<ApiResponse<CompactOption[]>>('/employment-types/compact');
    return response.data.data;
  },

  async getEmploymentTypeById(id: number): Promise<EmploymentTypeItem> {
    const response = await apiClient.get<ApiResponse<EmploymentTypeItem>>(`/employment-types/${id}`);
    return response.data.data;
  },

  async createEmploymentType(payload: CreateEmploymentTypePayload): Promise<EmploymentTypeItem> {
    const response = await apiClient.post<ApiResponse<EmploymentTypeItem>>('/employment-types', payload);
    return response.data.data;
  },

  async updateEmploymentType(id: number, payload: UpdateEmploymentTypePayload): Promise<EmploymentTypeItem> {
    const response = await apiClient.put<ApiResponse<EmploymentTypeItem>>(`/employment-types/${id}`, payload);
    return response.data.data;
  },

  async deleteEmploymentType(id: number): Promise<void> {
    await apiClient.delete(`/employment-types/${id}`);
  },
};
