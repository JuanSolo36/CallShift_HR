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
import { WorkPeriodModal } from '../components/WorkPeriodModal';
import { ChangeStatusModal } from '../components/ChangeStatusModal';
import { useWorkPeriods } from '../hooks/useWorkPeriods';
import { useDepartments } from '@/features/organization/hooks/useDepartments';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  CalendarDays,
  Plus,
  Search,
  Edit2,
  Trash2,
  Building2,
  Calendar,
  Layers,
  ArrowRightLeft,
} from 'lucide-react';
import type {
  WorkPeriodItem,
  CreateWorkPeriodPayload,
  UpdateWorkPeriodPayload,
  ChangeWorkPeriodStatusPayload,
  WorkPeriodStatus,
} from '@/types/workPeriod.types';

export const WorkPeriodsPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const { compactDepartments } = useDepartments();

  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [selectedDepartment, setSelectedDepartment] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    workPeriods,
    pagination,
    isLoading,
    createWorkPeriod,
    isCreating,
    updateWorkPeriod,
    isUpdating,
    changeStatus,
    isChangingStatus,
    deleteWorkPeriod,
    isDeleting,
  } = useWorkPeriods({
    search: searchTerm || undefined,
    status: selectedStatus || undefined,
    department_id: selectedDepartment ? Number(selectedDepartment) : undefined,
    page: currentPage,
    per_page: 10,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [periodToEdit, setPeriodToEdit] = useState<WorkPeriodItem | null>(null);
  const [periodToChangeStatus, setPeriodToChangeStatus] = useState<WorkPeriodItem | null>(null);
  const [periodToDelete, setPeriodToDelete] = useState<WorkPeriodItem | null>(null);

  const canCreate = hasPermission('schedules:create');
  const canUpdate = hasPermission('schedules:update') || hasPermission('schedules:create');
  const canPublish = hasPermission('schedules:publish') || hasPermission('schedules:create');

  const handleOpenCreate = () => {
    setPeriodToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (period: WorkPeriodItem) => {
    setPeriodToEdit(period);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreateWorkPeriodPayload | UpdateWorkPeriodPayload) => {
    if (periodToEdit) {
      updateWorkPeriod(
        { id: periodToEdit.id, payload: payload as UpdateWorkPeriodPayload },
        { onSuccess: () => setIsModalOpen(false) }
      );
    } else {
      createWorkPeriod(payload as CreateWorkPeriodPayload, {
        onSuccess: () => setIsModalOpen(false),
      });
    }
  };

  const handleStatusSubmit = (payload: ChangeWorkPeriodStatusPayload) => {
    if (periodToChangeStatus) {
      changeStatus(
        { id: periodToChangeStatus.id, payload },
        { onSuccess: () => setPeriodToChangeStatus(null) }
      );
    }
  };

  const renderStatusBadge = (status: WorkPeriodStatus) => {
    switch (status) {
      case 'DRAFT':
        return <Badge variant="neutral" size="sm" dot>Borrador</Badge>;
      case 'GENERATED':
        return <Badge variant="brand" size="sm" dot>Generado</Badge>;
      case 'REVIEW':
        return <Badge variant="warning" size="sm" dot>En Revisión</Badge>;
      case 'PUBLISHED':
        return <Badge variant="success" size="sm" dot>Publicado</Badge>;
      case 'CLOSED':
        return <Badge variant="neutral" size="sm">Cerrado</Badge>;
      default:
        return <Badge variant="neutral" size="sm">{status}</Badge>;
    }
  };

  const statusOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'DRAFT', label: 'Borradores' },
    { value: 'GENERATED', label: 'Generados' },
    { value: 'REVIEW', label: 'En Revisión' },
    { value: 'PUBLISHED', label: 'Publicados' },
    { value: 'CLOSED', label: 'Cerrados' },
  ];

  const departmentOptions = [
    { value: '', label: 'Todos los departamentos' },
    ...compactDepartments.map((d) => ({ value: String(d.id), label: d.name })),
  ];

  return (
    <div className="space-y-6 text-left select-none">
      <PageHeader
        title="Periodos Laborales y Ciclos de Planificación"
        description="Definición de ventanas temporales, semanas de turno y control de versiones de horarios."
        breadcrumbs={[
          { label: 'Horarios y Turnos' },
          { label: 'Periodos Laborales', current: true },
        ]}
        actions={
          canCreate && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Periodo
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <Input
              placeholder="Buscar por nombre de periodo..."
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
            <LoadingState message="Cargando periodos laborales..." />
          ) : workPeriods.length === 0 ? (
            <EmptyState
              icon={<CalendarDays className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron periodos laborales"
              description="No hay periodos registrados que coincidan con los filtros aplicados."
              actionText={canCreate ? 'Crear Periodo Laboral' : undefined}
              onAction={canCreate ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Periodo / Ventana</TableHead>
                    <TableHead>Departamento</TableHead>
                    <TableHead>Rango de Fechas</TableHead>
                    <TableHead>Versión Activa</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {workPeriods.map((period) => (
                    <TableRow key={period.id}>
                      <TableCell>
                        <div className="font-semibold text-surface-900 leading-tight">
                          {period.name}
                        </div>
                        <div className="text-[11px] text-surface-500 mt-0.5 flex items-center gap-1.5">
                          <span className="font-mono text-brand-700 bg-brand-50 px-1.5 py-0.2 rounded text-[10px]">
                            {period.period_type}
                          </span>
                          <span>{period.duration_days} días</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1.5 text-xs text-surface-800">
                          <Building2 className="w-3.5 h-3.5 text-surface-400" />
                          <span>{period.department?.name || 'Toda la Empresa (Global)'}</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1.5 text-xs font-mono text-surface-900">
                          <Calendar className="w-3.5 h-3.5 text-surface-400" />
                          <span>
                            {period.start_date} → {period.end_date}
                          </span>
                        </div>
                      </TableCell>

                      <TableCell>
                        {period.current_version ? (
                          <div className="flex items-center gap-1 text-xs font-medium text-surface-800">
                            <Layers className="w-3.5 h-3.5 text-brand-600" />
                            <span>V{period.current_version.version_number}</span>
                            <span className="text-[10px] text-surface-400 font-mono">
                              (lock #{period.current_version.lock_version})
                            </span>
                          </div>
                        ) : (
                          <span className="text-surface-400 text-xs italic">Sin Versión</span>
                        )}
                      </TableCell>

                      <TableCell>
                        {renderStatusBadge(period.status)}
                      </TableCell>

                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-1">
                          {canPublish && period.status !== 'CLOSED' && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setPeriodToChangeStatus(period)}
                              title="Cambiar estado del periodo"
                              className="h-8 w-8 p-0 text-surface-500 hover:text-brand-600"
                            >
                              <ArrowRightLeft className="w-4 h-4" />
                            </Button>
                          )}

                          {canUpdate && period.status !== 'CLOSED' && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => handleOpenEdit(period)}
                              title="Editar fechas/parámetros"
                              className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                            >
                              <Edit2 className="w-4 h-4" />
                            </Button>
                          )}

                          {canCreate && period.status === 'DRAFT' && (
                            <Button
                              variant="ghost"
                              size="sm"
                              onClick={() => setPeriodToDelete(period)}
                              title="Eliminar periodo borrador"
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

      <WorkPeriodModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        periodToEdit={periodToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      <ChangeStatusModal
        isOpen={!!periodToChangeStatus}
        onClose={() => setPeriodToChangeStatus(null)}
        period={periodToChangeStatus}
        onSubmit={handleStatusSubmit}
        isLoading={isChangingStatus}
      />

      <ConfirmDialog
        isOpen={!!periodToDelete}
        onClose={() => setPeriodToDelete(null)}
        onConfirm={() => {
          if (periodToDelete) {
            deleteWorkPeriod(periodToDelete.id, {
              onSuccess: () => setPeriodToDelete(null),
            });
          }
        }}
        title="¿Eliminar periodo laboral?"
        message={`¿Está seguro de que desea eliminar el periodo '${periodToDelete?.name}'? Esta acción solo es posible para periodos en estado Borrador.`}
        confirmText="Eliminar Periodo"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
