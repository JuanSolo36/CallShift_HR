import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { workPeriodService } from '../services/workPeriodService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  CreateWorkPeriodPayload,
  UpdateWorkPeriodPayload,
  ChangeWorkPeriodStatusPayload,
  WorkPeriodFilters,
} from '@/types/workPeriod.types';

export const useWorkPeriods = (filters: WorkPeriodFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['work-periods', filters];

  const periodsQuery = useQuery({
    queryKey,
    queryFn: () => workPeriodService.getWorkPeriods(filters),
    placeholderData: (prev) => prev,
    staleTime: 60 * 1000,
  });

  const compactQuery = useQuery({
    queryKey: ['work-periods', 'compact', filters.department_id],
    queryFn: () => workPeriodService.getWorkPeriodsCompact({ department_id: filters.department_id }),
    staleTime: 5 * 60 * 1000,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateWorkPeriodPayload) => workPeriodService.createWorkPeriod(payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['work-periods'] });
      addToast({
        type: 'success',
        title: 'Periodo Creado',
        message: `El periodo laboral '${data.name}' ha sido registrado exitosamente con su versión inicial.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al crear el periodo laboral.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateWorkPeriodPayload }) =>
      workPeriodService.updateWorkPeriod(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['work-periods'] });
      addToast({
        type: 'success',
        title: 'Periodo Actualizado',
        message: `El periodo laboral '${data.name}' ha sido modificado.`,
      });
    },
    onError: (error: any) => {
      if (error.response?.status === 409) {
        addToast({
          type: 'error',
          title: 'Conflicto de Concurrencia',
          message: error.response.data?.message || 'El periodo fue modificado por otro usuario. Recargue la página.',
        });
      } else {
        const message = error.response?.data?.message || 'Error al actualizar el periodo laboral.';
        addToast({ type: 'error', title: 'Error', message });
      }
    },
  });

  const changeStatusMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: ChangeWorkPeriodStatusPayload }) =>
      workPeriodService.changeWorkPeriodStatus(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['work-periods'] });
      addToast({
        type: 'success',
        title: 'Estado Actualizado',
        message: `El periodo '${data.name}' ahora está en estado '${data.status_label}'.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al cambiar el estado del periodo.';
      addToast({ type: 'error', title: 'Error de Estado', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => workPeriodService.deleteWorkPeriod(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['work-periods'] });
      addToast({
        type: 'success',
        title: 'Periodo Eliminado',
        message: 'El periodo laboral ha sido eliminado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al eliminar el periodo laboral.';
      addToast({ type: 'error', title: 'Error de Eliminación', message });
    },
  });

  return {
    workPeriods: periodsQuery.data?.data || [],
    pagination: periodsQuery.data?.meta,
    isLoading: periodsQuery.isLoading,
    isError: periodsQuery.isError,
    error: periodsQuery.error,
    refetch: periodsQuery.refetch,
    compactWorkPeriods: compactQuery.data || [],
    createWorkPeriod: createMutation.mutate,
    isCreating: createMutation.isPending,
    updateWorkPeriod: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    changeStatus: changeStatusMutation.mutate,
    isChangingStatus: changeStatusMutation.isPending,
    deleteWorkPeriod: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
