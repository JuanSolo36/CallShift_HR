import { apiClient } from '@/lib/axios';
import type {
  EmployeeReportItem,
  ScheduleReportItem,
  HoursReportData,
  AbsenceReportItem,
  ModificationReportItem,
  ReportFilters,
} from '@/types/reports.types';
import type { AuditLogItem } from '@/types/audit.types';

export const reportService = {
  // 1. Empleados
  async getEmployeesReport(filters: ReportFilters = {}): Promise<{ data: EmployeeReportItem[]; meta: any }> {
    const { data } = await apiClient.get<{ success: boolean; data: { data: EmployeeReportItem[]; meta: any } }>(
      '/reports/employees',
      { params: filters }
    );
    return data.data;
  },

  async exportEmployees(filters: ReportFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/reports/employees/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },

  // 2. Horarios
  async getSchedulesReport(filters: ReportFilters = {}): Promise<{ data: ScheduleReportItem[]; meta: any }> {
    const { data } = await apiClient.get<{ success: boolean; data: { data: ScheduleReportItem[]; meta: any } }>(
      '/reports/schedules',
      { params: filters }
    );
    return data.data;
  },

  async exportSchedules(filters: ReportFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/reports/schedules/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },

  // 3. Horas
  async getHoursReport(filters: ReportFilters = {}): Promise<HoursReportData> {
    const { data } = await apiClient.get<{ success: boolean; data: HoursReportData }>(
      '/reports/hours',
      { params: filters }
    );
    return data.data;
  },

  async exportHours(filters: ReportFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/reports/hours/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },

  // 4. Ausencias
  async getAbsencesReport(filters: ReportFilters = {}): Promise<{ data: AbsenceReportItem[]; meta: any }> {
    const { data } = await apiClient.get<{ success: boolean; data: { data: AbsenceReportItem[]; meta: any } }>(
      '/reports/absences',
      { params: filters }
    );
    return data.data;
  },

  async exportAbsences(filters: ReportFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/reports/absences/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },

  // 5. Modificaciones
  async getModificationsReport(filters: ReportFilters = {}): Promise<{ data: ModificationReportItem[]; meta: any }> {
    const { data } = await apiClient.get<{ success: boolean; data: { data: ModificationReportItem[]; meta: any } }>(
      '/reports/modifications',
      { params: filters }
    );
    return data.data;
  },

  async exportModifications(filters: ReportFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/reports/modifications/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },

  // 6. Auditoría
  async getAuditReport(filters: ReportFilters = {}): Promise<{ data: AuditLogItem[]; meta: any }> {
    const { data } = await apiClient.get<{ success: boolean; data: { data: AuditLogItem[]; meta: any } }>(
      '/reports/audit',
      { params: filters }
    );
    return data.data;
  },

  async exportAudit(filters: ReportFilters = {}): Promise<Blob> {
    const response = await apiClient.get('/reports/audit/export', {
      params: filters,
      responseType: 'blob',
    });
    return response.data;
  },
};
