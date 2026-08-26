import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { employmentTypeService } from '../services/employmentTypeService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  CreateEmploymentTypePayload,
  UpdateEmploymentTypePayload,
  EmploymentTypeFilters,
} from '@/types/employmentType.types';

export const useEmploymentTypes = (filters: EmploymentTypeFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['employment-types', filters];

  const typesQuery = useQuery({
    queryKey,
    queryFn: () => employmentTypeService.getEmploymentTypes(filters),
    placeholderData: (prev) => prev,
    staleTime: 60 * 1000,
  });

  const compactQuery = useQuery({
    queryKey: ['employment-types', 'compact'],
    queryFn: employmentTypeService.getEmploymentTypesCompact,
    staleTime: 5 * 60 * 1000,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateEmploymentTypePayload) =>
      employmentTypeService.createEmploymentType(payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['employment-types'] });
      addToast({
        type: 'success',
        title: 'Tipo de Contrato Creado',
        message: `El tipo de contrato '${data.name}' ha sido registrado exitosamente.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al crear el tipo de contrato.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateEmploymentTypePayload }) =>
      employmentTypeService.updateEmploymentType(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['employment-types'] });
      addToast({
        type: 'success',
        title: 'Tipo de Contrato Actualizado',
        message: `El tipo de contrato '${data.name}' ha sido actualizado.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar el tipo de contrato.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => employmentTypeService.deleteEmploymentType(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employment-types'] });
      addToast({
        type: 'success',
        title: 'Tipo de Contrato Eliminado',
        message: 'El tipo de contrato ha sido eliminado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al eliminar el tipo de contrato.';
      addToast({ type: 'error', title: 'Error de Eliminación', message });
    },
  });

  return {
    employmentTypes: typesQuery.data?.data || [],
    pagination: typesQuery.data?.meta,
    isLoading: typesQuery.isLoading,
    isError: typesQuery.isError,
    error: typesQuery.error,
    refetch: typesQuery.refetch,
    compactEmploymentTypes: compactQuery.data || [],
    createEmploymentType: createMutation.mutate,
    isCreating: createMutation.isPending,
    updateEmploymentType: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    deleteEmploymentType: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
