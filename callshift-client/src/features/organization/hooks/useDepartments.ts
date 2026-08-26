import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { departmentService } from '../services/departmentService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  CreateDepartmentPayload,
  UpdateDepartmentPayload,
  DepartmentFilters,
} from '@/types/organization.types';

export const useDepartments = (filters: DepartmentFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['departments', filters];

  const departmentsQuery = useQuery({
    queryKey,
    queryFn: () => departmentService.getDepartments(filters),
    placeholderData: (prev) => prev,
    staleTime: 60 * 1000,
  });

  const compactQuery = useQuery({
    queryKey: ['departments', 'compact'],
    queryFn: departmentService.getDepartmentsCompact,
    staleTime: 5 * 60 * 1000,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateDepartmentPayload) => departmentService.createDepartment(payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['departments'] });
      addToast({
        type: 'success',
        title: 'Departamento Creado',
        message: `El departamento '${data.name}' ha sido registrado exitosamente.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al crear el departamento.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateDepartmentPayload }) =>
      departmentService.updateDepartment(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['departments'] });
      addToast({
        type: 'success',
        title: 'Departamento Actualizado',
        message: `El departamento '${data.name}' ha sido actualizado.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar el departamento.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => departmentService.deleteDepartment(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['departments'] });
      addToast({
        type: 'success',
        title: 'Departamento Eliminado',
        message: 'El departamento ha sido eliminado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al eliminar el departamento.';
      addToast({ type: 'error', title: 'Error de Eliminación', message });
    },
  });

  return {
    departments: departmentsQuery.data?.data || [],
    pagination: departmentsQuery.data?.meta,
    isLoading: departmentsQuery.isLoading,
    isError: departmentsQuery.isError,
    error: departmentsQuery.error,
    refetch: departmentsQuery.refetch,
    compactDepartments: compactQuery.data || [],
    createDepartment: createMutation.mutate,
    isCreating: createMutation.isPending,
    updateDepartment: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    deleteDepartment: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
