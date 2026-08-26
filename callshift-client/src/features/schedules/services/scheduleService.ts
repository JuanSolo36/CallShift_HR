import { apiClient } from '@/lib/axios';
import type {
  ScheduleGridData,
  ScheduleAssignmentItem,
  UpsertAssignmentPayload,
  UpsertAssignmentResponse,
  DeleteAssignmentPayload,
  DeleteAssignmentResponse,
} from '@/types/schedule.types';

export const scheduleService = {
  /**
   * Obtiene la matriz completa para un periodo laboral.
   */
  async getScheduleByPeriod(workPeriodId: number, versionId?: number): Promise<ScheduleGridData> {
    const params = versionId ? { version_id: versionId } : {};
    const { data } = await apiClient.get<{ data: ScheduleGridData }>(
      `/work-periods/${workPeriodId}/schedule`,
      { params }
    );
    return data.data;
  },

  /**
   * Obtiene la matriz completa para una versión de horario.
   */
  async getGridByVersion(versionId: number): Promise<ScheduleGridData> {
    const { data } = await apiClient.get<{ data: ScheduleGridData }>(
      `/schedule-versions/${versionId}/grid`
    );
    return data.data;
  },

  /**
   * Lista las asignaciones de una versión.
   */
  async listAssignments(versionId: number): Promise<ScheduleAssignmentItem[]> {
    const { data } = await apiClient.get<{ data: ScheduleAssignmentItem[] }>(
      `/schedule-versions/${versionId}/assignments`
    );
    return data.data;
  },

  /**
   * Guarda o actualiza una asignación en una celda (Upsert).
   */
  async upsertAssignment(
    versionId: number,
    payload: UpsertAssignmentPayload
  ): Promise<UpsertAssignmentResponse> {
    const { data } = await apiClient.post<{ data: UpsertAssignmentResponse }>(
      `/schedule-versions/${versionId}/assignments`,
      payload
    );
    return data.data;
  },

  /**
   * Elimina / libera una celda de turno.
   */
  async deleteAssignment(
    versionId: number,
    assignmentId: number,
    payload: DeleteAssignmentPayload
  ): Promise<DeleteAssignmentResponse> {
    const { data } = await apiClient.delete<{ data: DeleteAssignmentResponse }>(
      `/schedule-versions/${versionId}/assignments/${assignmentId}`,
      { data: payload }
    );
    return data.data;
  },

  /**
   * Lista las modificaciones de una versión de horario.
   */
  async listModifications(versionId: number) {
    const { data } = await apiClient.get<{ data: any[] }>(
      `/schedule-versions/${versionId}/modifications`
    );
    return data.data;
  },

  /**
   * Registra una modificación controlada con motivo y evidencias.
   */
  async createModification(versionId: number, payload: any, files?: File[]) {
    const formData = new FormData();
    Object.entries(payload).forEach(([key, val]) => {
      if (val !== undefined && val !== null) {
        formData.append(key, String(val));
      }
    });

    if (files && files.length > 0) {
      files.forEach((file) => {
        formData.append('evidences[]', file);
      });
    }

    const { data } = await apiClient.post<{ data: any }>(
      `/schedule-versions/${versionId}/modifications`,
      formData,
      {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      }
    );
    return data.data;
  },

  /**
   * Obtiene el detalle de una modificación con sus snapshots y evidencias.
   */
  async getModification(id: number) {
    const { data } = await apiClient.get<{ data: any }>(
      `/schedule-modifications/${id}`
    );
    return data.data;
  },

  /**
   * Elimina una evidencia adjunta (solo en DRAFT).
   */
  async deleteEvidence(modificationId: number, evidenceId: number) {
    const { data } = await apiClient.delete<{ data: null }>(
      `/schedule-modifications/${modificationId}/evidences/${evidenceId}`
    );
    return data.data;
  },
};
