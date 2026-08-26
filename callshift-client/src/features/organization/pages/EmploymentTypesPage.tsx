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
import { EmploymentTypeModal } from '../components/EmploymentTypeModal';
import { useEmploymentTypes } from '../hooks/useEmploymentTypes';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  FileText,
  Plus,
  Search,
  Edit2,
  Trash2,
  Clock,
  Users,
} from 'lucide-react';
import type {
  EmploymentTypeItem,
  CreateEmploymentTypePayload,
  UpdateEmploymentTypePayload,
} from '@/types/employmentType.types';

export const EmploymentTypesPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    employmentTypes,
    pagination,
    isLoading,
    createEmploymentType,
    isCreating,
    updateEmploymentType,
    isUpdating,
    deleteEmploymentType,
    isDeleting,
  } = useEmploymentTypes({
    search: searchTerm || undefined,
    status: selectedStatus || undefined,
    page: currentPage,
    per_page: 10,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [typeToEdit, setTypeToEdit] = useState<EmploymentTypeItem | null>(null);
  const [typeToDelete, setTypeToDelete] = useState<EmploymentTypeItem | null>(null);

  const canManage = hasPermission('organization:manage');

  const handleOpenCreate = () => {
    setTypeToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (item: EmploymentTypeItem) => {
    setTypeToEdit(item);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreateEmploymentTypePayload | UpdateEmploymentTypePayload) => {
    if (typeToEdit) {
      updateEmploymentType(
        { id: typeToEdit.id, payload: payload as UpdateEmploymentTypePayload },
        { onSuccess: () => setIsModalOpen(false) }
      );
    } else {
      createEmploymentType(payload as CreateEmploymentTypePayload, {
        onSuccess: () => setIsModalOpen(false),
      });
    }
  };

  const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'ACTIVE', label: 'Activos' },
    { value: 'INACTIVE', label: 'Inactivos' },
  ];

  return (
    <div className="space-y-6 text-left select-none">
      <PageHeader
        title="Tipos de Contrato y Régimen Laboral"
        description="Gestión de modalidades contractuales, jornadas laborales base y esquemas de vinculación."
        breadcrumbs={[
          { label: 'Organización' },
          { label: 'Tipos de Contrato', current: true },
        ]}
        actions={
          canManage && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Tipo de Contrato
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
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
            <LoadingState message="Cargando tipos de contrato..." />
          ) : employmentTypes.length === 0 ? (
            <EmptyState
              icon={<FileText className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron tipos de contrato"
              description="No hay registros que coincidan con los filtros aplicados."
              actionText={canManage ? 'Crear Tipo de Contrato' : undefined}
              onAction={canManage ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Modalidad / Régimen</TableHead>
                    <TableHead>Código</TableHead>
                    <TableHead>Jornada Semanal</TableHead>
                    <TableHead>Empleados Asignados</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {employmentTypes.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell>
                        <div className="font-semibold text-surface-900 leading-tight">
                          {item.name}
                        </div>
                        {item.description && (
                          <div className="text-[11px] text-surface-400 truncate max-w-xs">
                            {item.description}
                          </div>
                        )}
                      </TableCell>

                      <TableCell>
                        <span className="font-mono text-xs text-brand-700 bg-brand-50 px-2 py-0.5 rounded">
                          {item.code}
                        </span>
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1.5 text-xs text-surface-800 font-medium">
                          <Clock className="w-3.5 h-3.5 text-brand-600" />
                          <span>{item.default_weekly_hours} hrs / semana</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1 text-xs text-surface-600">
                          <Users className="w-3.5 h-3.5 text-surface-400" />
                          <span>{item.employees_count ?? 0}</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        {item.status === 'ACTIVE' ? (
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
                                onClick={() => handleOpenEdit(item)}
                                title="Editar tipo de contrato"
                                className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                              >
                                <Edit2 className="w-4 h-4" />
                              </Button>

                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setTypeToDelete(item)}
                                title="Eliminar tipo de contrato"
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

      <EmploymentTypeModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        typeToEdit={typeToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      <ConfirmDialog
        isOpen={!!typeToDelete}
        onClose={() => setTypeToDelete(null)}
        onConfirm={() => {
          if (typeToDelete) {
            deleteEmploymentType(typeToDelete.id, {
              onSuccess: () => setTypeToDelete(null),
            });
          }
        }}
        title="¿Eliminar tipo de contrato?"
        message={`¿Está seguro de que desea eliminar el tipo de contrato '${typeToDelete?.name}'? Esta acción solo es posible si no se encuentra asignado a colaboradores.`}
        confirmText="Eliminar Tipo de Contrato"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
