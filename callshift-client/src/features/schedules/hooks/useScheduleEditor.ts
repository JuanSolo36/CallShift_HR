import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { scheduleService } from '../services/scheduleService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  ScheduleGridData,
  UpsertAssignmentPayload,
  DeleteAssignmentPayload,
} from '@/types/schedule.types';

interface UseScheduleEditorProps {
  workPeriodId?: number;
  versionId?: number;
}

export function useScheduleEditor({ workPeriodId, versionId }: UseScheduleEditorProps) {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['schedule-grid', workPeriodId, versionId];

  const {
    data: gridData,
    isLoading,
    isError,
    error,
    refetch,
  } = useQuery<ScheduleGridData>({
    queryKey,
    queryFn: async () => {
      if (versionId) {
        return scheduleService.getGridByVersion(versionId);
      }
      if (workPeriodId) {
        return scheduleService.getScheduleByPeriod(workPeriodId, versionId);
      }
      throw new Error('Debe especificar un periodo laboral o ID de versión.');
    },
    enabled: !!workPeriodId || !!versionId,
    staleTime: 30000,
  });

  const upsertMutation = useMutation({
    mutationFn: async ({
      versionId,
      payload,
    }: {
      versionId: number;
      payload: UpsertAssignmentPayload;
    }) => {
      return scheduleService.upsertAssignment(versionId, payload);
    },
    onSuccess: (data) => {
      // Actualizar caché de manera sincronizada
      queryClient.setQueryData<ScheduleGridData | undefined>(queryKey, (old) => {
        if (!old) return old;
        const newAssignments = old.assignments.filter(
          (a) =>
            !(
              a.employee_id === data.assignment.employee_id &&
              a.date === data.assignment.date
            )
        );
        return {
          ...old,
          version: {
            ...old.version,
            lock_version: data.lock_version,
          },
          assignments: [...newAssignments, data.assignment],
        };
      });

      addToast({
        type: 'success',
        title: 'Turno Asignado',
        message: 'La celda de horario se actualizó correctamente.',
      });
    },
    onError: (err: any) => {
      if (err?.response?.status === 409) {
        addToast({
          type: 'error',
          title: 'Conflicto de Concurrencia (409)',
          message:
            err?.response?.data?.message ||
            'Otro usuario actualizó el horario simultáneamente. Recargando datos más recientes...',
          duration: 6000,
        });
        refetch();
      } else {
        addToast({
          type: 'error',
          title: 'Error al Guardar Turno',
          message: err?.response?.data?.message || 'Ocurrió un error al asignar el turno.',
        });
      }
    },
  });

  const deleteMutation = useMutation({
    mutationFn: async ({
      versionId,
      assignmentId,
      payload,
    }: {
      versionId: number;
      assignmentId: number;
      payload: DeleteAssignmentPayload;
    }) => {
      return scheduleService.deleteAssignment(versionId, assignmentId, payload);
    },
    onSuccess: (data, variables) => {
      queryClient.setQueryData<ScheduleGridData | undefined>(queryKey, (old) => {
        if (!old) return old;
        return {
          ...old,
          version: {
            ...old.version,
            lock_version: data.lock_version,
          },
          assignments: old.assignments.filter((a) => a.id !== variables.assignmentId),
        };
      });

      addToast({
        type: 'info',
        title: 'Turno Liberado',
        message: 'Se eliminó la asignación de la celda seleccionada.',
      });
    },
    onError: (err: any) => {
      if (err?.response?.status === 409) {
        addToast({
          type: 'error',
          title: 'Conflicto de Concurrencia (409)',
          message: 'El horario cambió mientras se intentaba liberar el turno. Recargando...',
          duration: 6000,
        });
        refetch();
      } else {
        addToast({
          type: 'error',
          title: 'Error al Liberar Turno',
          message: err?.response?.data?.message || 'No fue posible eliminar la asignación.',
        });
      }
    },
  });

  return {
    gridData,
    isLoading,
    isError,
    error,
    refetch,
    upsertAssignment: upsertMutation.mutate,
    isUpserting: upsertMutation.isPending,
    deleteAssignment: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
}
