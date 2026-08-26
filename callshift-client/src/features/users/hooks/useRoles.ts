import { useQuery } from '@tanstack/react-query';
import { roleService } from '../services/roleService';

export const useRoles = () => {
  const rolesQuery = useQuery({
    queryKey: ['roles'],
    queryFn: roleService.getRoles,
    staleTime: 10 * 60 * 1000, // Roles son estables durante 10 minutos
  });

  const permissionsQuery = useQuery({
    queryKey: ['permissions'],
    queryFn: roleService.getPermissions,
    staleTime: 15 * 60 * 1000,
  });

  return {
    roles: rolesQuery.data || [],
    isLoadingRoles: rolesQuery.isLoading,
    permissions: permissionsQuery.data || [],
    isLoadingPermissions: permissionsQuery.isLoading,
  };
};
