import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { companyService } from '../services/companyService';
import { useUIStore } from '@/stores/useUIStore';
import type { UpdateCompanyPayload, UpdateCompanySettingsPayload } from '@/types/company.types';

export const useCompany = () => {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const companyQuery = useQuery({
    queryKey: ['company'],
    queryFn: companyService.getCompany,
    staleTime: 5 * 60 * 1000,
  });

  const updateCompanyMutation = useMutation({
    mutationFn: (payload: UpdateCompanyPayload) => companyService.updateCompany(payload),
    onSuccess: (data) => {
      queryClient.setQueryData(['company'], data);
      queryClient.invalidateQueries({ queryKey: ['auth', 'me'] });
      addToast({
        type: 'success',
        title: 'Empresa Actualizada',
        message: 'La información corporativa ha sido guardada correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar la empresa.';
      addToast({
        type: 'error',
        title: 'Error',
        message,
      });
    },
  });

  const updateSettingsMutation = useMutation({
    mutationFn: (payload: UpdateCompanySettingsPayload) => companyService.updateSettings(payload),
    onSuccess: (data) => {
      queryClient.setQueryData(['company'], data);
      addToast({
        type: 'success',
        title: 'Configuración Guardada',
        message: 'Los ajustes del sistema se han actualizado correctamente.',
      });
    },
    onError: (error: any) => {
      const message = error.response?.data?.message || 'Error al actualizar los ajustes.';
      addToast({
        type: 'error',
        title: 'Error',
        message,
      });
    },
  });

  return {
    company: companyQuery.data,
    isLoading: companyQuery.isLoading,
    isError: companyQuery.isError,
    error: companyQuery.error,
    refetch: companyQuery.refetch,
    updateCompany: updateCompanyMutation.mutate,
    isUpdatingCompany: updateCompanyMutation.isPending,
    updateSettings: updateSettingsMutation.mutate,
    isUpdatingSettings: updateSettingsMutation.isPending,
  };
};
