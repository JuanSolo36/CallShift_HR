import { QueryClient } from '@tanstack/react-query';

export const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 3, // 3 minutos de vigencia de caché por defecto
      gcTime: 1000 * 60 * 10,   // 10 minutos en memoria de recolección
      refetchOnWindowFocus: false,
      retry: (failureCount, error: unknown) => {
        // No reintentar en errores 401, 403 o 404
        if (typeof error === 'object' && error !== null && 'response' in error) {
          const status = (error as { response?: { status?: number } }).response?.status;
          if (status === 401 || status === 403 || status === 404) return false;
        }
        return failureCount < 2;
      },
    },
  },
});
