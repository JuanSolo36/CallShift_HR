import { apiClient } from '@/lib/axios';
import type { BusinessRule, BusinessRuleFormData, EffectiveBusinessRules } from '../types/businessRule';
import type { ApiResponse } from '../types/api.types';

export const businessRuleService = {
  async getEffective(departmentId?: number | null): Promise<EffectiveBusinessRules> {
    const params = departmentId ? { department_id: departmentId } : {};
    const res = await apiClient.get<ApiResponse<EffectiveBusinessRules>>('/business-rules/effective', { params });
    return res.data.data;
  },

  async list(): Promise<BusinessRule[]> {
    const res = await apiClient.get<ApiResponse<BusinessRule[]>>('/business-rules');
    return res.data.data;
  },

  async getById(id: number): Promise<BusinessRule> {
    const res = await apiClient.get<ApiResponse<BusinessRule>>(`/business-rules/${id}`);
    return res.data.data;
  },

  async save(data: BusinessRuleFormData): Promise<BusinessRule> {
    const res = await apiClient.post<ApiResponse<BusinessRule>>('/business-rules', data);
    return res.data.data;
  },

  async update(id: number, data: BusinessRuleFormData): Promise<BusinessRule> {
    const res = await apiClient.put<ApiResponse<BusinessRule>>(`/business-rules/${id}`, data);
    return res.data.data;
  },

  async delete(id: number): Promise<void> {
    await apiClient.delete(`/business-rules/${id}`);
  },
};
