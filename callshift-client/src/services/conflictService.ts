import { apiClient } from '@/lib/axios';
import type { ScheduleConflict, ConflictValidationResponse } from '../types/conflict';
import type { ApiResponse } from '../types/api.types';

export interface ConflictFilters {
  status?: string;
  severity?: string;
  rule_violated?: string;
  employee_id?: number;
}

export const conflictService = {
  async validateVersion(versionId: number): Promise<ConflictValidationResponse> {
    const res = await apiClient.post<ConflictValidationResponse>(`/schedule-versions/${versionId}/validate`);
    return res.data;
  },

  async getConflicts(versionId: number, filters?: ConflictFilters): Promise<ScheduleConflict[]> {
    const res = await apiClient.get<ApiResponse<ScheduleConflict[]>>(`/schedule-versions/${versionId}/conflicts`, {
      params: filters,
    });
    return res.data.data;
  },

  async resolveConflict(conflictId: number, reason: string): Promise<ScheduleConflict> {
    const res = await apiClient.patch<ApiResponse<ScheduleConflict>>(`/schedule-conflicts/${conflictId}/resolve`, {
      reason,
    });
    return res.data.data;
  },
};
