import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { positionService } from '../services/positionService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  CreatePositionPayload,
  UpdatePositionPayload,
  PositionFilters,
} from '@/types/organization.types';

export const usePositions = (filters: PositionFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['positions', filters];

  const positionsQuery = useQuery({
    queryKey,
    queryFn: () => positionService.getPositions(filters),
    placeholderData: (prev) => prev,
    staleTime: 60 * 1000,
  });

  const compactQuery = useQuery({
    queryKey: ['positions', 'compact', filters.department_id],
    queryFn: () => positionService.getPositionsCompact(filters.department_id ? Number(filters.department_id) : undefined),
    staleTime: 5 * 60 * 1000,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreatePositionPayload) => positionService.createPosition(payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['positions'] });
      addToast({
        type: 'success',
        title: 'Cargo Creado',
        message: `El cargo '${data.name}' ha sido registrado exitosamente.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al crear el cargo.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdatePositionPayload }) =>
      positionService.updatePosition(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['positions'] });
      addToast({
        type: 'success',
        title: 'Cargo Actualizado',
        message: `El cargo '${data.name}' ha sido actualizado.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar el cargo.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => positionService.deletePosition(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['positions'] });
      addToast({
        type: 'success',
        title: 'Cargo Eliminado',
        message: 'El cargo ha sido eliminado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al eliminar el cargo.';
      addToast({ type: 'error', title: 'Error de Eliminación', message });
    },
  });

  return {
    positions: positionsQuery.data?.data || [],
    pagination: positionsQuery.data?.meta,
    isLoading: positionsQuery.isLoading,
    isError: positionsQuery.isError,
    error: positionsQuery.error,
    refetch: positionsQuery.refetch,
    compactPositions: compactQuery.data || [],
    createPosition: createMutation.mutate,
    isCreating: createMutation.isPending,
    updatePosition: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    deletePosition: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
