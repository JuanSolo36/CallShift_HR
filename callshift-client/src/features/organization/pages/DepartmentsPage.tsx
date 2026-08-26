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
import { DepartmentModal } from '../components/DepartmentModal';
import { useDepartments } from '../hooks/useDepartments';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  FolderTree,
  Plus,
  Search,
  Edit2,
  Trash2,
  Briefcase,
  Users,
} from 'lucide-react';
import type { DepartmentItem, CreateDepartmentPayload, UpdateDepartmentPayload } from '@/types/organization.types';

export const DepartmentsPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    departments,
    pagination,
    isLoading,
    createDepartment,
    isCreating,
    updateDepartment,
    isUpdating,
    deleteDepartment,
    isDeleting,
  } = useDepartments({
    search: searchTerm || undefined,
    status: selectedStatus || undefined,
    page: currentPage,
    per_page: 10,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [deptToEdit, setDeptToEdit] = useState<DepartmentItem | null>(null);
  const [deptToDelete, setDeptToDelete] = useState<DepartmentItem | null>(null);

  const canManage = hasPermission('organization:manage');

  const handleOpenCreate = () => {
    setDeptToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (dept: DepartmentItem) => {
    setDeptToEdit(dept);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreateDepartmentPayload | UpdateDepartmentPayload) => {
    if (deptToEdit) {
      updateDepartment(
        { id: deptToEdit.id, payload: payload as UpdateDepartmentPayload },
        { onSuccess: () => setIsModalOpen(false) }
      );
    } else {
      createDepartment(payload as CreateDepartmentPayload, {
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
        title="Departamentos y Áreas"
        description="Estructura departamental, centros de costo y asignación de responsabilidades."
        breadcrumbs={[
          { label: 'Organización' },
          { label: 'Departamentos', current: true },
        ]}
        actions={
          canManage && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Departamento
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <Input
              placeholder="Buscar por nombre, código o centro de costo..."
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
            <LoadingState message="Cargando departamentos..." />
          ) : departments.length === 0 ? (
            <EmptyState
              icon={<FolderTree className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron departamentos"
              description="No hay departamentos registrados que coincidan con los filtros aplicados."
              actionText={canManage ? 'Crear Departamento' : undefined}
              onAction={canManage ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Departamento</TableHead>
                    <TableHead>Código</TableHead>
                    <TableHead>Centro de Costo</TableHead>
                    <TableHead>Cargos Asignados</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {departments.map((dept) => (
                    <TableRow key={dept.id}>
                      <TableCell>
                        <div className="font-semibold text-surface-900 leading-tight">
                          {dept.name}
                        </div>
                        {dept.description && (
                          <div className="text-[11px] text-surface-400 truncate max-w-xs">
                            {dept.description}
                          </div>
                        )}
                      </TableCell>

                      <TableCell>
                        <span className="font-mono text-xs text-brand-700 bg-brand-50 px-2 py-0.5 rounded">
                          {dept.code}
                        </span>
                      </TableCell>

                      <TableCell>
                        {dept.cost_center_code ? (
                          <span className="font-mono text-xs text-surface-600">
                            {dept.cost_center_code}
                          </span>
                        ) : (
                          <span className="text-surface-400 text-xs italic">N/A</span>
                        )}
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-3 text-xs text-surface-600">
                          <div className="flex items-center gap-1" title="Cargos en esta área">
                            <Briefcase className="w-3.5 h-3.5 text-surface-400" />
                            <span>{dept.positions_count ?? 0}</span>
                          </div>
                          <div className="flex items-center gap-1" title="Empleados vinculados">
                            <Users className="w-3.5 h-3.5 text-surface-400" />
                            <span>{dept.employees_count ?? 0}</span>
                          </div>
                        </div>
                      </TableCell>

                      <TableCell>
                        {dept.status === 'ACTIVE' ? (
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
                                onClick={() => handleOpenEdit(dept)}
                                title="Editar departamento"
                                className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                              >
                                <Edit2 className="w-4 h-4" />
                              </Button>

                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setDeptToDelete(dept)}
                                title="Eliminar departamento"
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

      <DepartmentModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        departmentToEdit={deptToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      <ConfirmDialog
        isOpen={!!deptToDelete}
        onClose={() => setDeptToDelete(null)}
        onConfirm={() => {
          if (deptToDelete) {
            deleteDepartment(deptToDelete.id, {
              onSuccess: () => setDeptToDelete(null),
            });
          }
        }}
        title="¿Eliminar departamento?"
        message={`¿Está seguro de que desea eliminar el departamento '${deptToDelete?.name}'? Esta acción solo es posible si no contiene cargos o empleados asociados.`}
        confirmText="Eliminar Departamento"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
