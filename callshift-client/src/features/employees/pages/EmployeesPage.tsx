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
import { EmployeeModal } from '../components/EmployeeModal';
import { useEmployees } from '../hooks/useEmployees';
import { useDepartments } from '@/features/organization/hooks/useDepartments';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  Users,
  UserPlus,
  Search,
  Edit2,
  Trash2,
  Briefcase,
  Building2,
  Mail,
  Phone,
  UserCheck,
} from 'lucide-react';
import type {
  EmployeeItem,
  CreateEmployeePayload,
  UpdateEmployeePayload,
  EmployeeStatus,
} from '@/types/employee.types';

export const EmployeesPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const { compactDepartments } = useDepartments();

  const [searchTerm, setSearchTerm] = useState('');
  const [selectedDepartment, setSelectedDepartment] = useState<string>('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    employees,
    pagination,
    isLoading,
    createEmployee,
    isCreating,
    updateEmployee,
    isUpdating,
    deleteEmployee,
    isDeleting,
  } = useEmployees({
    search: searchTerm || undefined,
    department_id: selectedDepartment ? Number(selectedDepartment) : undefined,
    status: selectedStatus || undefined,
    page: currentPage,
    per_page: 10,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [employeeToEdit, setEmployeeToEdit] = useState<EmployeeItem | null>(null);
  const [employeeToDelete, setEmployeeToDelete] = useState<EmployeeItem | null>(null);

  const canCreate = hasPermission('employees:create');
  const canUpdate = hasPermission('employees:update');
  const canDelete = hasPermission('employees:delete');

  const handleOpenCreate = () => {
    setEmployeeToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (employee: EmployeeItem) => {
    setEmployeeToEdit(employee);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreateEmployeePayload | UpdateEmployeePayload) => {
    if (employeeToEdit) {
      updateEmployee(
        { id: employeeToEdit.id, payload: payload as UpdateEmployeePayload },
        { onSuccess: () => setIsModalOpen(false) }
      );
    } else {
      createEmployee(payload as CreateEmployeePayload, {
        onSuccess: () => setIsModalOpen(false),
      });
    }
  };

  const renderStatusBadge = (status: EmployeeStatus) => {
    switch (status) {
      case 'ACTIVE':
        return <Badge variant="success" size="sm" dot>Activo</Badge>;
      case 'INACTIVE':
        return <Badge variant="neutral" size="sm" dot>Inactivo</Badge>;
      case 'ON_LEAVE':
        return <Badge variant="warning" size="sm" dot>En Licencia</Badge>;
      case 'TERMINATED':
        return <Badge variant="danger" size="sm" dot>Retirado</Badge>;
      default:
        return <Badge variant="neutral" size="sm">{status}</Badge>;
    }
  };

  const statusOptions = [
    { value: '', label: 'Todos los estados laborales' },
    { value: 'ACTIVE', label: 'Activos' },
    { value: 'INACTIVE', label: 'Inactivos' },
    { value: 'ON_LEAVE', label: 'En Licencia' },
    { value: 'TERMINATED', label: 'Retirados' },
  ];

  const departmentOptions = [
    { value: '', label: 'Todos los departamentos' },
    ...compactDepartments.map((d) => ({ value: String(d.id), label: d.name })),
  ];

  return (
    <div className="space-y-6 text-left select-none">
      <PageHeader
        title="Colaboradores y Expedientes Laborales"
        description="Directorio institucional de colaboradores, asignación de turnos y datos contractuales."
        breadcrumbs={[
          { label: 'Recursos Humanos' },
          { label: 'Empleados', current: true },
        ]}
        actions={
          canCreate && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<UserPlus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Empleado
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <Input
              placeholder="Buscar por nombre, código, cédula o email..."
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
            <LoadingState message="Cargando colaboradores..." />
          ) : employees.length === 0 ? (
            <EmptyState
              icon={<Users className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron colaboradores"
              description="No hay registros de empleados que coincidan con los criterios de búsqueda."
              actionText={canCreate ? 'Registrar Empleado' : undefined}
              onAction={canCreate ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Colaborador</TableHead>
                    <TableHead>Documento / Identificación</TableHead>
                    <TableHead>Área y Cargo</TableHead>
                    <TableHead>Tipo de Contrato</TableHead>
                    <TableHead>Supervisor Directo</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {employees.map((emp) => (
                    <TableRow key={emp.id}>
                      <TableCell>
                        <div className="font-semibold text-surface-900 leading-tight">
                          {emp.full_name}
                        </div>
                        <div className="flex items-center gap-2 mt-0.5 text-xs text-surface-500">
                          <span className="font-mono text-brand-700 bg-brand-50 px-1.5 py-0.2 rounded text-[11px]">
                            {emp.employee_code}
                          </span>
                          <span className="flex items-center gap-1">
                            <Mail className="w-3 h-3 text-surface-400" />
                            {emp.email}
                          </span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="text-xs text-surface-900 font-mono font-medium">
                          {emp.document_type}: {emp.document_number}
                        </div>
                        {emp.phone && (
                          <div className="flex items-center gap-1 text-[11px] text-surface-500 mt-0.5">
                            <Phone className="w-3 h-3 text-surface-400" />
                            {emp.phone}
                          </div>
                        )}
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1.5 text-xs font-medium text-surface-900">
                          <Building2 className="w-3.5 h-3.5 text-surface-400" />
                          <span>{emp.department?.name || 'Sin Área'}</span>
                        </div>
                        <div className="flex items-center gap-1.5 text-[11px] text-surface-500 mt-0.5">
                          <Briefcase className="w-3 h-3 text-surface-400" />
                          <span>{emp.position?.name || 'Sin Cargo'}</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="text-xs text-surface-800">
                          {emp.employment_type?.name || 'N/A'}
                        </div>
                        {emp.employment_type?.default_weekly_hours && (
                          <div className="text-[11px] text-surface-400">
                            {emp.employment_type.default_weekly_hours} hrs/sem
                          </div>
                        )}
                      </TableCell>

                      <TableCell>
                        {emp.supervisor ? (
                          <div className="flex items-center gap-1.5 text-xs text-surface-700">
                            <UserCheck className="w-3.5 h-3.5 text-brand-600" />
                            <span>{emp.supervisor.full_name}</span>
                          </div>
                        ) : (
                          <span className="text-surface-400 text-xs italic">Ninguno</span>
                        )}
                      </TableCell>

                      <TableCell>
                        {renderStatusBadge(emp.status)}
                      </TableCell>

                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canUpdate && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleOpenEdit(emp)}
                              title="Editar expediente"
                              className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                            >
                              <Edit2 className="w-4 h-4" />
                            </Button>
                          )}

                          {canDelete && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setEmployeeToDelete(emp)}
                              title="Eliminar colaborador"
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

      <EmployeeModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        employeeToEdit={employeeToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      <ConfirmDialog
        isOpen={!!employeeToDelete}
        onClose={() => setEmployeeToDelete(null)}
        onConfirm={() => {
          if (employeeToDelete) {
            deleteEmployee(employeeToDelete.id, {
              onSuccess: () => setEmployeeToDelete(null),
            });
          }
        }}
        title="¿Eliminar colaborador del sistema?"
        message={`¿Está seguro de que desea eliminar el expediente de '${employeeToDelete?.full_name}' [${employeeToDelete?.employee_code}]? Esta acción solo se permite si no cuenta con colaboradores a su cargo.`}
        confirmText="Eliminar Empleado"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
