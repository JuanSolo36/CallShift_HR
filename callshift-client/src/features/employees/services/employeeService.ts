import { apiClient } from '@/lib/axios';
import type { ApiResponse, PaginatedResponse } from '@/types/api.types';
import type {
  EmployeeItem,
  CreateEmployeePayload,
  UpdateEmployeePayload,
  EmployeeFilters,
  CompactEmployeeOption,
} from '@/types/employee.types';

export const employeeService = {
  async getEmployees(filters: EmployeeFilters = {}): Promise<PaginatedResponse<EmployeeItem>> {
    const response = await apiClient.get<PaginatedResponse<EmployeeItem>>('/employees', {
      params: filters,
    });
    return response.data;
  },

  async getEmployeesCompact(excludeId?: number): Promise<CompactEmployeeOption[]> {
    const response = await apiClient.get<ApiResponse<CompactEmployeeOption[]>>('/employees/compact', {
      params: excludeId ? { exclude_id: excludeId } : {},
    });
    return response.data.data;
  },

  async getEmployeeById(id: number): Promise<EmployeeItem> {
    const response = await apiClient.get<ApiResponse<EmployeeItem>>(`/employees/${id}`);
    return response.data.data;
  },

  async createEmployee(payload: CreateEmployeePayload): Promise<EmployeeItem> {
    const response = await apiClient.post<ApiResponse<EmployeeItem>>('/employees', payload);
    return response.data.data;
  },

  async updateEmployee(id: number, payload: UpdateEmployeePayload): Promise<EmployeeItem> {
    const response = await apiClient.put<ApiResponse<EmployeeItem>>(`/employees/${id}`, payload);
    return response.data.data;
  },

  async changeEmployeeStatus(id: number, status: string, reason?: string): Promise<EmployeeItem> {
    const response = await apiClient.patch<ApiResponse<EmployeeItem>>(`/employees/${id}/status`, {
      status,
      reason,
    });
    return response.data.data;
  },

  async deleteEmployee(id: number): Promise<void> {
    await apiClient.delete(`/employees/${id}`);
  },
};
