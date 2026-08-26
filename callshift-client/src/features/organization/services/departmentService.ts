import { apiClient } from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type {
  DepartmentItem,
  CreateDepartmentPayload,
  UpdateDepartmentPayload,
  DepartmentFilters,
  CompactOption,
} from '@/types/organization.types';

export const departmentService = {
  async getDepartments(filters: DepartmentFilters = {}): Promise<PaginatedResponse<DepartmentItem>> {
    const response = await apiClient.get<PaginatedResponse<DepartmentItem>>('/departments', {
      params: filters,
    });
    return response.data;
  },

  async getDepartmentsCompact(): Promise<CompactOption[]> {
    const response = await apiClient.get<ApiResponse<CompactOption[]>>('/departments/compact');
    return response.data.data;
  },

  async getDepartmentById(id: number): Promise<DepartmentItem> {
    const response = await apiClient.get<ApiResponse<DepartmentItem>>(`/departments/${id}`);
    return response.data.data;
  },

  async createDepartment(payload: CreateDepartmentPayload): Promise<DepartmentItem> {
    const response = await apiClient.post<ApiResponse<DepartmentItem>>('/departments', payload);
    return response.data.data;
  },

  async updateDepartment(id: number, payload: UpdateDepartmentPayload): Promise<DepartmentItem> {
    const response = await apiClient.put<ApiResponse<DepartmentItem>>(`/departments/${id}`, payload);
    return response.data.data;
  },

  async deleteDepartment(id: number): Promise<void> {
    await apiClient.delete(`/departments/${id}`);
  },
};
