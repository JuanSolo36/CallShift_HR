import { apiClient } from '@/lib/axios';
import type { AuditLogItem, AuditFilters } from '@/types/audit.types';

export const auditService = {
  /**
   * Obtiene la lista paginada y filtrada de registros de auditoría.
   */
  async listLogs(filters: AuditFilters = {}): Promise<{ data: AuditLogItem[]; meta: any }> {
    const { data } = await apiClient.get<{ success: boolean; data: { data: AuditLogItem[]; meta: any } }>(
      '/audit-logs',
      { params: filters }
    );
    return data.data;
  },

  /**
   * Obtiene el detalle completo de un registro de auditoría.
   */
  async getLog(id: number): Promise<AuditLogItem> {
    const { data } = await apiClient.get<{ success: boolean; data: AuditLogItem }>(
      `/audit-logs/${id}`
    );
    return data.data;
  },

  /**
   * Exporta la bitácora de auditoría a CSV.
   */
  async exportLogsCsv(filters: AuditFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/audit-logs/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },
};
