import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { shiftPatternService, ShiftPatternFilters } from '@/services/shiftPatternService';
import { ShiftPattern, ShiftTemplate, ApplyPatternPayload } from '@/types/shiftPattern';
import { useUIStore } from '@/stores/useUIStore';

export const PATTERNS_QUERY_KEY = ['shift-patterns'];
export const TEMPLATES_QUERY_KEY = ['shift-templates'];

export function useShiftPatterns(filters?: ShiftPatternFilters) {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const patternsQuery = useQuery({
    queryKey: [...PATTERNS_QUERY_KEY, filters],
    queryFn: () => shiftPatternService.getPatterns(filters),
    staleTime: 1000 * 60 * 5,
  });

  const createMutation = useMutation({
    mutationFn: (data: Partial<ShiftPattern>) => shiftPatternService.createPattern(data),
    onSuccess: (newPattern) => {
      queryClient.invalidateQueries({ queryKey: PATTERNS_QUERY_KEY });
      addToast({
        type: 'success',
        title: 'Patrón creado',
        message: `El patrón '${newPattern.name}' fue registrado exitosamente.`,
      });
    },
    onError: (error: any) => {
      addToast({
        type: 'error',
        title: 'Error al crear patrón',
        message: error.response?.data?.message || 'Ocurrió un error al registrar el patrón de turno.',
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<ShiftPattern> }) =>
      shiftPatternService.updatePattern(id, data),
    onSuccess: (updated) => {
      queryClient.invalidateQueries({ queryKey: PATTERNS_QUERY_KEY });
      addToast({
        type: 'success',
        title: 'Patrón actualizado',
        message: `El patrón '${updated.name}' fue actualizado exitosamente.`,
      });
    },
    onError: (error: any) => {
      addToast({
        type: 'error',
        title: 'Error al actualizar patrón',
        message: error.response?.data?.message || 'Ocurrió un error al actualizar el patrón.',
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => shiftPatternService.deletePattern(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: PATTERNS_QUERY_KEY });
      addToast({
        type: 'success',
        title: 'Patrón eliminado',
        message: 'El patrón de turno fue eliminado exitosamente.',
      });
    },
    onError: (error: any) => {
      addToast({
        type: 'error',
        title: 'Error al eliminar patrón',
        message: error.response?.data?.message || 'Ocurrió un error al eliminar el patrón.',
      });
    },
  });

  return {
    patterns: patternsQuery.data || [],
    isLoading: patternsQuery.isLoading,
    isError: patternsQuery.isError,
    error: patternsQuery.error,
    createPattern: createMutation.mutateAsync,
    updatePattern: updateMutation.mutateAsync,
    deletePattern: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending,
  };
}

export function useShiftTemplates(filters?: { status?: string; department_id?: number }) {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const templatesQuery = useQuery({
    queryKey: [...TEMPLATES_QUERY_KEY, filters],
    queryFn: () => shiftPatternService.getTemplates(filters),
    staleTime: 1000 * 60 * 5,
  });

  const createMutation = useMutation({
    mutationFn: (data: Partial<ShiftTemplate>) => shiftPatternService.createTemplate(data),
    onSuccess: (newTpl) => {
      queryClient.invalidateQueries({ queryKey: TEMPLATES_QUERY_KEY });
      addToast({
        type: 'success',
        title: 'Plantilla creada',
        message: `La plantilla '${newTpl.name}' fue registrada exitosamente.`,
      });
    },
    onError: (error: any) => {
      addToast({
        type: 'error',
        title: 'Error al crear plantilla',
        message: error.response?.data?.message || 'Ocurrió un error al registrar la plantilla.',
      });
    },
  });

  const updateMutation = useMutation({
    mutationFn: ({ id, data }: { id: number; data: Partial<ShiftTemplate> }) =>
      shiftPatternService.updateTemplate(id, data),
    onSuccess: (updated) => {
      queryClient.invalidateQueries({ queryKey: TEMPLATES_QUERY_KEY });
      addToast({
        type: 'success',
        title: 'Plantilla actualizada',
        message: `La plantilla '${updated.name}' fue actualizada exitosamente.`,
      });
    },
    onError: (error: any) => {
      addToast({
        type: 'error',
        title: 'Error al actualizar plantilla',
        message: error.response?.data?.message || 'Ocurrió un error al actualizar la plantilla.',
      });
    },
  });

  const deleteMutation = useMutation({
    mutationFn: (id: number) => shiftPatternService.deleteTemplate(id),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: TEMPLATES_QUERY_KEY });
      addToast({
        type: 'success',
        title: 'Plantilla eliminada',
        message: 'La plantilla fue eliminada exitosamente.',
      });
    },
    onError: (error: any) => {
      addToast({
        type: 'error',
        title: 'Error al eliminar plantilla',
        message: error.response?.data?.message || 'Ocurrió un error al eliminar la plantilla.',
      });
    },
  });

  return {
    templates: templatesQuery.data || [],
    isLoading: templatesQuery.isLoading,
    isError: templatesQuery.isError,
    createTemplate: createMutation.mutateAsync,
    updateTemplate: updateMutation.mutateAsync,
    deleteTemplate: deleteMutation.mutateAsync,
    isCreating: createMutation.isPending,
    isUpdating: updateMutation.isPending,
    isDeleting: deleteMutation.isPending,
  };
}

export function usePatternApplication(versionId: number) {
  const queryClient = useQueryClient();
  const { addToast } = useUIStore();

  const previewMutation = useMutation({
    mutationFn: (payload: ApplyPatternPayload) =>
      shiftPatternService.previewPatternApplication(versionId, payload),
  });

  const applyMutation = useMutation({
    mutationFn: (payload: ApplyPatternPayload) =>
      shiftPatternService.applyPattern(versionId, payload),
    onSuccess: (res) => {
      queryClient.invalidateQueries({ queryKey: ['schedules', 'grid'] });
      queryClient.invalidateQueries({ queryKey: ['work-periods'] });
      addToast({
        type: 'success',
        title: 'Patrón aplicado',
        message: res.message || 'El patrón fue aplicado exitosamente a la versión de horario.',
      });
    },
    onError: (error: any) => {
      const isConflict = error.response?.status === 409;
      addToast({
        type: 'error',
        title: isConflict ? 'Conflicto de concurrencia' : 'Error al aplicar patrón',
        message:
          error.response?.data?.message ||
          'Ocurrió un error al aplicar el patrón de turno sobre la malla horaria.',
      });
    },
  });

  return {
    previewPattern: previewMutation.mutateAsync,
    applyPattern: applyMutation.mutateAsync,
    isPreviewing: previewMutation.isPending,
    isApplying: applyMutation.isPending,
  };
}
