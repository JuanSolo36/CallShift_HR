import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { shiftTypeService } from '../services/shiftTypeService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  CreateShiftTypePayload,
  UpdateShiftTypePayload,
  ShiftTypeFilters,
} from '@/types/shiftType.types';

export const useShiftTypes = (filters: ShiftTypeFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['shift-types', filters];

  const typesQuery = useQuery({
    queryKey,
    queryFn: () => shiftTypeService.getShiftTypes(filters),
    placeholderData: (prev) => prev,
    staleTime: 60 * 1000,
  });

  const compactQuery = useQuery({
    queryKey: ['shift-types', 'compact'],
    queryFn: () => shiftTypeService.getShiftTypesCompact(),
    staleTime: 5 * 60 * 1000,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateShiftTypePayload) => shiftTypeService.createShiftType(payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['shift-types'] });
      addToast({
        type: 'success',
        title: 'Tipo de Turno Creado',
        message: `El tipo de turno '${data.name}' [${data.code}] ha sido registrado exitosamente.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al crear el tipo de turno.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateShiftTypePayload }) =>
      shiftTypeService.updateShiftType(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['shift-types'] });
      addToast({
        type: 'success',
        title: 'Tipo de Turno Actualizado',
        message: `El tipo de turno '${data.name}' ha sido actualizado.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar el tipo de turno.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => shiftTypeService.deleteShiftType(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['shift-types'] });
      addToast({
        type: 'success',
        title: 'Tipo de Turno Eliminado',
        message: 'El tipo de turno ha sido eliminado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al eliminar el tipo de turno.';
      addToast({ type: 'error', title: 'Error de Eliminación', message });
    },
  });

  return {
    shiftTypes: typesQuery.data?.data || [],
    pagination: typesQuery.data?.meta,
    isLoading: typesQuery.isLoading,
    isError: typesQuery.isError,
    error: typesQuery.error,
    refetch: typesQuery.refetch,
    compactShiftTypes: compactQuery.data || [],
    createShiftType: createMutation.mutate,
    isCreating: createMutation.isPending,
    updateShiftType: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    deleteShiftType: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
