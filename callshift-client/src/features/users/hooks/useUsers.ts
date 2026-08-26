import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { userService } from '../services/userService';
import { useUIStore } from '@/stores/useUIStore';
import type { UserFilters, CreateUserPayload, UpdateUserPayload } from '@/types/user.types';
import type { AxiosError } from 'axios';
import type { ApiResponse } from '@/types/api.types';

export const useUsers = (filters: UserFilters = {}) => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const usersQuery = useQuery({
    queryKey: ['users', filters],
    queryFn: () => userService.getUsers(filters),
    placeholderData: (previousData) => previousData,
  });

  const createMutation = useMutation({
    mutationFn: (payload: CreateUserPayload) => userService.createUser(payload),
    onSuccess: (newUser) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      addToast({
        type: 'success',
        title: 'Usuario Creado',
        message: `El usuario '${newUser.username}' ha sido registrado exitosamente.`,
      });
    },
    onError: (error: AxiosError<ApiResponse>) => {
      const message = error.response?.data?.message || 'Error al crear el usuario.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, payload }: { id: number; payload: UpdateUserPayload }) =>
      userService.updateUser(id, payload),
    onSuccess: (updatedUser) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      addToast({
        type: 'success',
        title: 'Usuario Actualizado',
        message: `El usuario '${updatedUser.username}' fue modificado con éxito.`,
      });
    },
    onError: (error: AxiosError<ApiResponse>) => {
      const message = error.response?.data?.message || 'Error al actualizar el usuario.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const statusMutation = useMutation({
    mutationFn: ({ id, status, reason }: { id: number; status: string; reason?: string }) =>
      userService.changeStatus(id, status, reason),
    onSuccess: (user) => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      addToast({
        type: 'info',
        title: 'Estado Actualizado',
        message: `El estado del usuario '${user.username}' ahora es ${user.status}.`,
      });
    },
    onError: (error: AxiosError<ApiResponse>) => {
      const message = error.response?.data?.message || 'No fue posible cambiar el estado del usuario.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => userService.deleteUser(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] });
      addToast({
        type: 'success',
        title: 'Usuario Eliminado',
        message: 'El usuario ha sido eliminado del sistema.',
      });
    },
    onError: (error: AxiosError<ApiResponse>) => {
      const message = error.response?.data?.message || 'Error al eliminar el usuario.';
      addToast({ type: 'error', title: 'Error', message });
    },
  });

  return {
    users: usersQuery.data?.data || [],
    pagination: usersQuery.data?.meta,
    isLoading: usersQuery.isLoading,
    isFetching: usersQuery.isFetching,
    error: usersQuery.error,
    refetch: usersQuery.refetch,
    createUser: createMutation.mutate,
    isCreating: createMutation.isPending,
    updateUser: updateMutation.mutate,
    isUpdating: updateMutation.isPending,
    changeStatus: statusMutation.mutate,
    isChangingStatus: statusMutation.isPending,
    deleteUser: deleteMutation.mutate,
    isDeleting: deleteMutation.isPending,
  };
};
