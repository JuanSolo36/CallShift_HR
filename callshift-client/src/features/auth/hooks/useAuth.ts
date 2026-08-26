import { useMutation, useQuery } from '@tanstack/react-query';
import { useNavigate } from 'react-router-dom';
import { useAuthStore } from '@/stores/useAuthStore';
import { useUIStore } from '@/stores/useUIStore';
import { authService, type LoginPayload, type ChangePasswordPayload } from '../services/authService';
import type { AxiosError } from 'axios';
import type { ApiResponse } from '@/types/api.types';

export const useAuth = () => {
  const navigate = useNavigate();
  const { user, isAuthenticated, setAuth, setUser, clearAuth } = useAuthStore();
  const { addToast } = useUIStore();

  // Consulta de perfil /me con revalidación y sincronización con el backend
  const meQuery = useQuery({
    queryKey: ['auth', 'me'],
    queryFn: async () => {
      try {
        const freshUser = await authService.getMe();
        setUser(freshUser);
        return freshUser;
      } catch (err: unknown) {
        const axiosErr = err as AxiosError;
        if (axiosErr.response?.status === 401) {
          clearAuth();
        }
        throw err;
      }
    },
    enabled: isAuthenticated,
    staleTime: 5 * 60 * 1000, // 5 minutos de vigencia antes de volver a consultar
    retry: false,
  });

  // Mutación de Login
  const loginMutation = useMutation({
    mutationFn: (payload: LoginPayload) => authService.login(payload),
    onSuccess: (data) => {
      setAuth(data.user, data.token);
      addToast({
        type: 'success',
        title: '¡Bienvenido!',
        message: `Sesión iniciada como ${data.user.username}`,
      });
      navigate('/dashboard');
    },
    onError: (error: AxiosError<ApiResponse>) => {
      const message = error.response?.data?.message || 'Error al iniciar sesión. Verifique sus credenciales.';
      addToast({
        type: 'error',
        title: 'Error de Autenticación',
        message,
      });
    },
  });

  // Mutación de Logout
  const logoutMutation = useMutation({
    mutationFn: authService.logout,
    onSettled: () => {
      clearAuth();
      addToast({
        type: 'info',
        title: 'Sesión Finalizada',
        message: 'Ha cerrado sesión correctamente.',
      });
      navigate('/login');
    },
  });

  // Mutación de Cambio de Contraseña
  const changePasswordMutation = useMutation({
    mutationFn: (payload: ChangePasswordPayload) => authService.changePassword(payload),
    onSuccess: () => {
      addToast({
        type: 'success',
        title: 'Contraseña Actualizada',
        message: 'Su contraseña ha sido modificada con éxito.',
      });
    },
    onError: (error: AxiosError<ApiResponse>) => {
      const message = error.response?.data?.message || 'No fue posible actualizar la contraseña.';
      addToast({
        type: 'error',
        title: 'Error',
        message,
      });
    },
  });

  return {
    user,
    isAuthenticated,
    isLoadingUser: meQuery.isLoading,
    login: loginMutation.mutate,
    isLoggingIn: loginMutation.isPending,
    logout: logoutMutation.mutate,
    isLoggingOut: logoutMutation.isPending,
    changePassword: changePasswordMutation.mutate,
    isChangingPassword: changePasswordMutation.isPending,
  };
};
