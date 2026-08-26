import { apiClient } from '@/lib/axios';
import type { ApiResponse } from '@/types/api.types';
import type { CompanyItem, UpdateCompanyPayload, UpdateCompanySettingsPayload } from '@/types/company.types';

export const companyService = {
  /**
   * Consulta la información de la empresa del tenant actual.
   */
  async getCompany(): Promise<CompanyItem> {
    const response = await apiClient.get<ApiResponse<CompanyItem>>('/company');
    return response.data.data;
  },

  /**
   * Actualiza la información corporativa de la empresa.
   */
  async updateCompany(payload: UpdateCompanyPayload): Promise<CompanyItem> {
    const response = await apiClient.put<ApiResponse<CompanyItem>>('/company', payload);
    return response.data.data;
  },

  /**
   * Actualiza las configuraciones regionales, visuales y parámetros del sistema.
   */
  async updateSettings(payload: UpdateCompanySettingsPayload): Promise<CompanyItem> {
    const response = await apiClient.patch<ApiResponse<CompanyItem>>('/company/settings', payload);
    return response.data.data;
  },
};
