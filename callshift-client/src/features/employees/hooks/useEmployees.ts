import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { employeeService } from '../services/employeeService';
import { useUIStore } from '@/stores/useUIStore';
import type {
  CreateEmployeePayload,
  UpdateEmployeePayload,
  EmployeeFilters,
} from '@/types/employee.types';

export const useEmployees = (filters: EmployeeFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const queryKey = ['employees', filters];

  const employeesQuery = useQuery({
    queryKey,
    queryFn: () => employeeService.getEmployees(filters),
    placeholderData: (prev) => prev,
    staleTime: 60 * 1000,
  });

  const compactQuery = useQuery({
    queryKey: ['employees', 'compact'],
    queryFn: () => employeeService.getEmployeesCompact(),
    staleTime: 5 * 60 * 1000,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateEmployeePayload) => employeeService.createEmployee(payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['employees'] });
      addToast({
        type: 'success',
        title: 'Empleado Registrado',
        message: `El expediente de '${data.full_name}' [${data.employee_code}] ha sido creado.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al registrar el empleado.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateEmployeePayload }) =>
      employeeService.updateEmployee(id, payload),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['employees'] });
      addToast({
        type: 'success',
        title: 'Expediente Actualizado',
        message: `Los datos de '${data.full_name}' han sido actualizados.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar el expediente.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status, reason }: { id: number; status: string; reason?: string }) =>
      employeeService.changeEmployeeStatus(id, status, reason),
    onSuccess: (data) => {
      queryClient.invalidateQueries({ queryKey: ['employees'] });
      addToast({
        type: 'success',
        title: 'Estado Laboral Modificado',
        message: `El estado de '${data.full_name}' ha cambiado a ${data.status}.`,
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al cambiar estado del empleado.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => employeeService.deleteEmployee(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['employees'] });
      addToast({
        type: 'success',
        title: 'Empleado Eliminado',
        message: 'El expediente del empleado ha sido eliminado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al eliminar el empleado.';
      addToast({ type: 'error', title: 'Error de Eliminación', message });
    },
  });

  return {
    employees: employeesQuery.data?.data || [],
    pagination: employeesQuery.data?.meta,
    isLoading: employeesQuery.isLoading,
    isError: employeesQuery.isError,
    error: employeesQuery.error,
    refetch: employeesQuery.refetch,
    compactEmployees: compactQuery.data || [],
    createEmployee: createMutation.mutate,
    isCreating: createMutation.isPending,
    updateEmployee: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    changeStatus: statusMutation.mutate,
    isChangingStatus: statusMutation.isPending,
    deleteEmployee: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
