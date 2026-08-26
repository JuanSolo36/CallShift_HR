import React, { useState } from 'react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardContent } from '@/components/ui/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Avatar } from '@/components/ui/Avatar';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { Pagination } from '@/components/navigation/Pagination';
import { EmptyState } from '@/components/feedback/EmptyState';
import { LoadingState } from '@/components/feedback/LoadingState';
import { ConfirmDialog } from '@/components/feedback/ConfirmDialog';
import { UserModal } from '../components/UserModal';
import { useUsers } from '../hooks/useUsers';
import { useRoles } from '../hooks/useRoles';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  UserPlus,
  Search,
  Users as UsersIcon,
  Shield,
  Edit2,
  Trash2,
  CheckCircle2,
  XCircle,
} from 'lucide-react';
import type { UserItem, CreateUserPayload, UpdateUserPayload } from '@/types/user.types';

export const UsersPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const { roles } = useRoles();

  // Filtros de consulta
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRole, setSelectedRole] = useState<string>('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    users,
    pagination,
    isLoading,
    createUser,
    isCreating,
    updateUser,
    isUpdating,
    changeStatus,
    deleteUser,
    isDeleting,
  } = useUsers({
    search: searchTerm || undefined,
    role_id: selectedRole ? Number(selectedRole) : undefined,
    status: selectedStatus || undefined,
    page: currentPage,
    per_page: 10,
  });

  // Modales
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [userToEdit, setUserToEdit] = useState<UserItem | null>(null);
  const [userToDelete, setUserToDelete] = useState<UserItem | null>(null);

  const handleOpenCreate = () => {
    setUserToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (user: UserItem) => {
    setUserToEdit(user);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreateUserPayload | UpdateUserPayload) => {
    if (userToEdit) {
      updateUser(
        { id: userToEdit.id, payload: payload as UpdateUserPayload },
        {
          onSuccess: () => setIsModalOpen(false),
        }
      );
    } else {
      createUser(payload as CreateUserPayload, {
        onSuccess: () => setIsModalOpen(false),
      });
    }
  };

  const handleToggleStatus = (user: UserItem) => {
    const nextStatus = user.status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';
    changeStatus({ id: user.id, status: nextStatus });
  };

  const roleOptions = [
    { value: '', label: 'Todos los roles' },
    ...roles.map((r) => ({ value: String(r.id), label: r.name })),
  ];

  const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'ACTIVE', label: 'Activos' },
    { value: 'INACTIVE', label: 'Inactivos' },
    { value: 'SUSPENDED', label: 'Suspendidos' },
  ];

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'ACTIVE':
        return <Badge variant="success" size="sm" dot>Activo</Badge>;
      case 'INACTIVE':
        return <Badge variant="neutral" size="sm" dot>Inactivo</Badge>;
      case 'SUSPENDED':
        return <Badge variant="danger" size="sm" dot>Suspendido</Badge>;
      default:
        return <Badge variant="neutral" size="sm">{status}</Badge>;
    }
  };

  return (
    <div className="space-y-6 text-left select-none">
      {/* Encabezado */}
      <PageHeader
        title="Gestión de Usuarios y Accesos"
        description="Administración centralizada de cuentas de usuario, roles RBAC y control de acceso."
        breadcrumbs={[
          { label: 'Administración' },
          { label: 'Usuarios', current: true },
        ]}
        actions={
          hasPermission('users:create') && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<UserPlus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Usuario
            </Button>
          )
        }
      />

      {/* Barra de Filtros */}
      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <Input
              placeholder="Buscar por usuario o correo..."
              leftIcon={<Search className="w-4 h-4" />}
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setCurrentPage(1);
              }}
            />

            <Select
              options={roleOptions}
              value={selectedRole}
              onChange={(e) => {
                setSelectedRole(e.target.value);
                setCurrentPage(1);
              }}
            />

            <Select
              options={statusOptions}
              value={selectedStatus}
              onChange={(e) => {
                setSelectedStatus(e.target.value);
                setCurrentPage(1);
              }}
            />
          </div>
        </CardContent>
      </Card>

      {/* Tabla de Usuarios */}
      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <LoadingState message="Cargando usuarios..." />
          ) : users.length === 0 ? (
            <EmptyState
              icon={<UsersIcon className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron usuarios"
              description="No hay registros que coincidan con los filtros aplicados."
              actionText={hasPermission('users:create') ? 'Crear Usuario' : undefined}
              onAction={hasPermission('users:create') ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Usuario</TableHead>
                    <TableHead>Rol y Permisos</TableHead>
                    <TableHead>Empleado Vinculado</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead>Último Acceso</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {users.map((userItem: UserItem) => (
                    <TableRow key={userItem.id}>
                      {/* Usuario y Email */}
                      <TableCell>
                        <div className="flex items-center gap-3">
                          <Avatar
                            name={userItem.employee?.full_name || userItem.username}
                            size="sm"
                            status={userItem.status === 'ACTIVE' ? 'online' : 'offline'}
                          />
                          <div>
                            <div className="font-semibold text-surface-900 leading-tight">
                              {userItem.username}
                            </div>
                            <div className="text-[11px] text-surface-400 font-mono">
                              {userItem.email}
                            </div>
                          </div>
                        </div>
                      </TableCell>

                      {/* Rol */}
                      <TableCell>
                        <div className="flex items-center gap-1.5">
                          <Shield className="w-3.5 h-3.5 text-brand-600 shrink-0" />
                          <span className="font-medium text-surface-800 text-xs">
                            {userItem.role?.name || 'Sin Rol'}
                          </span>
                        </div>
                      </TableCell>

                      {/* Empleado */}
                      <TableCell>
                        {userItem.employee ? (
                          <div>
                            <div className="text-xs font-medium text-surface-900">
                              {userItem.employee.full_name}
                            </div>
                            <div className="text-[10px] text-surface-400">
                              {userItem.employee.department?.name || 'General'}
                            </div>
                          </div>
                        ) : (
                          <span className="text-surface-400 text-xs italic">No vinculado</span>
                        )}
                      </TableCell>

                      {/* Estado */}
                      <TableCell>{getStatusBadge(userItem.status)}</TableCell>

                      {/* Último Acceso */}
                      <TableCell className="text-xs text-surface-500">
                        {userItem.last_login_at
                          ? new Date(userItem.last_login_at).toLocaleDateString('es-CO', {
                              day: '2-digit',
                              month: 'short',
                              year: 'numeric',
                              hour: '2-digit',
                              minute: '2-digit',
                            })
                          : 'Nunca'}
                      </TableCell>

                      {/* Acciones */}
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {hasPermission('users:update') && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleToggleStatus(userItem)}
                              title={userItem.status === 'ACTIVE' ? 'Desactivar usuario' : 'Activar usuario'}
                              className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                            >
                              {userItem.status === 'ACTIVE' ? (
                                <XCircle className="w-4 h-4 text-amber-600" />
                              ) : (
                                <CheckCircle2 className="w-4 h-4 text-emerald-600" />
                              )}
                            </Button>
                          )}

                          {hasPermission('users:update') && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleOpenEdit(userItem)}
                              title="Editar usuario"
                              className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                            >
                              <Edit2 className="w-4 h-4" />
                            </Button>
                          )}

                          {hasPermission('users:delete') && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setUserToDelete(userItem)}
                              title="Eliminar usuario"
                              className="h-8 w-8 p-0 text-surface-500 hover:text-rose-600"
                            >
                              <Trash2 className="w-4 h-4" />
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

              {/* Paginación */}
              {pagination && (
                <div className="px-4 border-t border-surface-100">
                  <Pagination
                    currentPage={pagination.current_page}
                    totalPages={pagination.last_page}
                    totalItems={pagination.total}
                    fromItem={pagination.from}
                    toItem={pagination.to}
                    onPageChange={(page) => setCurrentPage(page)}
                  />
                </div>
              )}
            </>
          )}
        </CardContent>
      </Card>

      {/* Modal de Creación / Edición */}
      <UserModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        userToEdit={userToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      {/* Diálogo de Confirmación de Eliminación */}
      <ConfirmDialog
        isOpen={!!userToDelete}
        onClose={() => setUserToDelete(null)}
        onConfirm={() => {
          if (userToDelete) {
            deleteUser(userToDelete.id, {
              onSuccess: () => setUserToDelete(null),
            });
          }
        }}
        title="¿Eliminar usuario?"
        message={`¿Está seguro de que desea eliminar la cuenta del usuario '${userToDelete?.username}'? Esta acción revocará todos sus tokens y accesos al sistema.`}
        confirmText="Eliminar Usuario"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
