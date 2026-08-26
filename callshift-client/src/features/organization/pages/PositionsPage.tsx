import React, { useState } from 'react';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardContent } from '@/components/ui/Card';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { Pagination } from '@/components/navigation/Pagination';
import { EmptyState } from '@/components/feedback/EmptyState';
import { LoadingState } from '@/components/feedback/LoadingState';
import { ConfirmDialog } from '@/components/feedback/ConfirmDialog';
import { PositionModal } from '../components/PositionModal';
import { usePositions } from '../hooks/usePositions';
import { useDepartments } from '../hooks/useDepartments';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  Briefcase,
  Plus,
  Search,
  Edit2,
  Trash2,
  Users,
} from 'lucide-react';
import type { PositionItem, CreatePositionPayload, UpdatePositionPayload } from '@/types/organization.types';

export const PositionsPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const { compactDepartments } = useDepartments();

  const [searchTerm, setSearchTerm] = useState('');
  const [selectedDepartment, setSelectedDepartment] = useState<string>('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    positions,
    pagination,
    isLoading,
    createPosition,
    isCreating,
    updatePosition,
    isUpdating,
    deletePosition,
    isDeleting,
  } = usePositions({
    search: searchTerm || undefined,
    department_id: selectedDepartment ? Number(selectedDepartment) : undefined,
    status: selectedStatus || undefined,
    page: currentPage,
    per_page: 10,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [positionToEdit, setPositionToEdit] = useState<PositionItem | null>(null);
  const [positionToDelete, setPositionToDelete] = useState<PositionItem | null>(null);

  const canManage = hasPermission('organization:manage');

  const handleOpenCreate = () => {
    setPositionToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (position: PositionItem) => {
    setPositionToEdit(position);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreatePositionPayload | UpdatePositionPayload) => {
    if (positionToEdit) {
      updatePosition(
        { id: positionToEdit.id, payload: payload as UpdatePositionPayload },
        { onSuccess: () => setIsModalOpen(false) }
      );
    } else {
      createPosition(payload as CreatePositionPayload, {
        onSuccess: () => setIsModalOpen(false),
      });
    }
  };

  const departmentOptions = [
    { value: '', label: 'Todos los departamentos' },
    ...compactDepartments.map((d) => ({ value: String(d.id), label: d.name })),
  ];

  const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'ACTIVE', label: 'Activos' },
    { value: 'INACTIVE', label: 'Inactivos' },
  ];

  return (
    <div className="space-y-6 text-left select-none">
      <PageHeader
        title="Cargos y Posiciones"
        description="Catálogo de roles organizacionales, puestos de trabajo y perfiles de cargo."
        breadcrumbs={[
          { label: 'Organización' },
          { label: 'Cargos', current: true },
        ]}
        actions={
          canManage && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Cargo
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <Input
              placeholder="Buscar por nombre o código..."
              leftIcon={<Search className="w-4 h-4" />}
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setCurrentPage(1);
              }}
            />

            <Select
              options={departmentOptions}
              value={selectedDepartment}
              onChange={(e) => {
                setSelectedDepartment(e.target.value);
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

      <Card>
        <CardContent className="p-0">
          {isLoading ? (
            <LoadingState message="Cargando cargos..." />
          ) : positions.length === 0 ? (
            <EmptyState
              icon={<Briefcase className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron cargos"
              description="No hay registros de cargos que coincidan con los filtros aplicados."
              actionText={canManage ? 'Crear Cargo' : undefined}
              onAction={canManage ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Cargo / Posición</TableHead>
                    <TableHead>Código</TableHead>
                    <TableHead>Departamento</TableHead>
                    <TableHead>Empleados Asignados</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {positions.map((pos) => (
                    <TableRow key={pos.id}>
                      <TableCell>
                        <div className="font-semibold text-surface-900 leading-tight">
                          {pos.name}
                        </div>
                        {pos.description && (
                          <div className="text-[11px] text-surface-400 truncate max-w-xs">
                            {pos.description}
                          </div>
                        )}
                      </TableCell>

                      <TableCell>
                        <span className="font-mono text-xs text-brand-700 bg-brand-50 px-2 py-0.5 rounded">
                          {pos.code}
                        </span>
                      </TableCell>

                      <TableCell>
                        {pos.department ? (
                          <div>
                            <div className="text-xs font-medium text-surface-900">
                              {pos.department.name}
                            </div>
                            <div className="text-[10px] text-surface-400 font-mono">
                              {pos.department.code}
                            </div>
                          </div>
                        ) : (
                          <span className="text-surface-400 text-xs italic">General (Sin Depto)</span>
                        )}
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1 text-xs text-surface-600">
                          <Users className="w-3.5 h-3.5 text-surface-400" />
                          <span>{pos.employees_count ?? 0}</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        {pos.status === 'ACTIVE' ? (
                          <Badge variant="success" size="sm" dot>Activo</Badge>
                        ) : (
                          <Badge variant="neutral" size="sm" dot>Inactivo</Badge>
                        )}
                      </TableCell>

                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canManage && (
                            <>
                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => handleOpenEdit(pos)}
                                title="Editar cargo"
                                className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                              >
                                <Edit2 className="w-4 h-4" />
                              </Button>

                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setPositionToDelete(pos)}
                                title="Eliminar cargo"
                                className="h-8 w-8 p-0 text-surface-500 hover:text-rose-600"
                              >
                                <Trash2 className="w-4 h-4" />
                              </Button>
                            </>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>

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

      <PositionModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        positionToEdit={positionToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      <ConfirmDialog
        isOpen={!!positionToDelete}
        onClose={() => setPositionToDelete(null)}
        onConfirm={() => {
          if (positionToDelete) {
            deletePosition(positionToDelete.id, {
              onSuccess: () => setPositionToDelete(null),
            });
          }
        }}
        title="¿Eliminar cargo?"
        message={`¿Está seguro de que desea eliminar el cargo '${positionToDelete?.name}'? Esta acción solo es posible si no cuenta con empleados asignados.`}
        confirmText="Eliminar Cargo"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
