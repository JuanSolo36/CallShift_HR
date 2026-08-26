import { apiClient } from '@/lib/axios';
import {
  ShiftPattern,
  ShiftTemplate,
  PatternPreviewResponse,
  ApplyPatternPayload,
  ApplyPatternResult,
} from '@/types/shiftPattern';

export interface ShiftPatternFilters {
  status?: string;
  department_id?: number;
  search?: string;
}

export const shiftPatternService = {
  // Patrones
  async getPatterns(filters?: ShiftPatternFilters): Promise<ShiftPattern[]> {
    const res = await apiClient.get('/shift-patterns', { params: filters });
    return res.data.data;
  },

  async getPattern(id: number): Promise<ShiftPattern> {
    const res = await apiClient.get(`/shift-patterns/${id}`);
    return res.data.data;
  },

  async createPattern(data: Partial<ShiftPattern>): Promise<ShiftPattern> {
    const res = await apiClient.post('/shift-patterns', data);
    return res.data.data;
  },

  async updatePattern(id: number, data: Partial<ShiftPattern>): Promise<ShiftPattern> {
    const res = await apiClient.put(`/shift-patterns/${id}`, data);
    return res.data.data;
  },

  async deletePattern(id: number): Promise<void> {
    await apiClient.delete(`/shift-patterns/${id}`);
  },

  // Plantillas
  async getTemplates(filters?: { status?: string; department_id?: number }): Promise<ShiftTemplate[]> {
    const res = await apiClient.get('/shift-templates', { params: filters });
    return res.data.data;
  },

  async getTemplate(id: number): Promise<ShiftTemplate> {
    const res = await apiClient.get(`/shift-templates/${id}`);
    return res.data.data;
  },

  async createTemplate(data: Partial<ShiftTemplate>): Promise<ShiftTemplate> {
    const res = await apiClient.post('/shift-templates', data);
    return res.data.data;
  },

  async updateTemplate(id: number, data: Partial<ShiftTemplate>): Promise<ShiftTemplate> {
    const res = await apiClient.put(`/shift-templates/${id}`, data);
    return res.data.data;
  },

  async deleteTemplate(id: number): Promise<void> {
    await apiClient.delete(`/shift-templates/${id}`);
  },

  // Aplicación sobre versiones de horario
  async previewPatternApplication(versionId: number, payload: ApplyPatternPayload): Promise<PatternPreviewResponse> {
    const res = await apiClient.post(`/schedule-versions/${versionId}/apply-pattern/preview`, payload);
    return res.data.data;
  },

  async applyPattern(versionId: number, payload: ApplyPatternPayload): Promise<ApplyPatternResult> {
    const res = await apiClient.post(`/schedule-versions/${versionId}/apply-pattern`, payload);
    return res.data.data;
  },
};
