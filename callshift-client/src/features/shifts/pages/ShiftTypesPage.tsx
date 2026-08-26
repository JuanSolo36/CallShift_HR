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
import { ShiftTypeModal } from '../components/ShiftTypeModal';
import { useShiftTypes } from '../hooks/useShiftTypes';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  CalendarClock,
  Plus,
  Search,
  Edit2,
  Trash2,
  Clock,
  Moon,
  Coffee,
} from 'lucide-react';
import type {
  ShiftTypeItem,
  CreateShiftTypePayload,
  UpdateShiftTypePayload,
} from '@/types/shiftType.types';

export const ShiftTypesPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedStatus, setSelectedStatus] = useState<string>('');
  const [currentPage, setCurrentPage] = useState(1);

  const {
    shiftTypes,
    pagination,
    isLoading,
    createShiftType,
    isCreating,
    updateShiftType,
    isUpdating,
    deleteShiftType,
    isDeleting,
  } = useShiftTypes({
    search: searchTerm || undefined,
    status: selectedStatus || undefined,
    page: currentPage,
    per_page: 10,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [shiftToEdit, setShiftToEdit] = useState<ShiftTypeItem | null>(null);
  const [shiftToDelete, setShiftToDelete] = useState<ShiftTypeItem | null>(null);

  const canManage = hasPermission('shifts:manage');

  const handleOpenCreate = () => {
    setShiftToEdit(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (shift: ShiftTypeItem) => {
    setShiftToEdit(shift);
    setIsModalOpen(true);
  };

  const handleFormSubmit = (payload: CreateShiftTypePayload | UpdateShiftTypePayload) => {
    if (shiftToEdit) {
      updateShiftType(
        { id: shiftToEdit.id, payload: payload as UpdateShiftTypePayload },
        { onSuccess: () => setIsModalOpen(false) }
      );
    } else {
      createShiftType(payload as CreateShiftTypePayload, {
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
        title="Tipos de Turno y Franjas Horarias"
        description="Configuración de turnos diurnos, nocturnos, descansos y cálculo de horas computables."
        breadcrumbs={[
          { label: 'Horarios y Turnos' },
          { label: 'Tipos de Turno', current: true },
        ]}
        actions={
          canManage && (
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={handleOpenCreate}
            >
              Nuevo Tipo de Turno
            </Button>
          )
        }
      />

      <Card>
        <CardContent className="p-4">
          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <Input
              placeholder="Buscar por nombre o código de turno..."
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
            <LoadingState message="Cargando tipos de turno..." />
          ) : shiftTypes.length === 0 ? (
            <EmptyState
              icon={<CalendarClock className="w-6 h-6 stroke-[1.5]" />}
              title="No se encontraron tipos de turno"
              description="No hay turnos registrados que coincidan con los filtros aplicados."
              actionText={canManage ? 'Crear Tipo de Turno' : undefined}
              onAction={canManage ? handleOpenCreate : undefined}
            />
          ) : (
            <>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Turno / Franja</TableHead>
                    <TableHead>Código</TableHead>
                    <TableHead>Horario Programado</TableHead>
                    <TableHead>Descanso</TableHead>
                    <TableHead>Horas Efectivas</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acciones</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {shiftTypes.map((shift) => (
                    <TableRow key={shift.id}>
                      <TableCell>
                        <div className="flex items-center gap-2.5">
                          <span
                            className="w-3.5 h-3.5 rounded-full flex-shrink-0 shadow-sm border border-black/10"
                            style={{ backgroundColor: shift.color_hex }}
                            title={`Color: ${shift.color_hex}`}
                          />
                          <div>
                            <div className="font-semibold text-surface-900 leading-tight">
                              {shift.name}
                            </div>
                            {shift.description && (
                              <div className="text-[11px] text-surface-400 truncate max-w-xs">
                                {shift.description}
                              </div>
                            )}
                          </div>
                        </div>
                      </TableCell>

                      <TableCell>
                        <span className="font-mono text-xs text-brand-700 bg-brand-50 px-2 py-0.5 rounded">
                          {shift.code}
                        </span>
                      </TableCell>

                      <TableCell>
                        <div className="space-y-1">
                          <div className="flex items-center gap-1.5 text-xs font-mono font-medium text-surface-900">
                            <Clock className="w-3.5 h-3.5 text-surface-400" />
                            <span>
                              {shift.start_time} - {shift.end_time}
                            </span>
                          </div>
                          {shift.crosses_midnight && (
                            <Badge variant="warning" size="sm" className="text-[10px] py-0 px-1.5 gap-1">
                              <Moon className="w-2.5 h-2.5" /> Cruza Medianoche
                            </Badge>
                          )}
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="flex items-center gap-1.5 text-xs text-surface-600">
                          <Coffee className="w-3.5 h-3.5 text-surface-400" />
                          <span>{shift.break_duration_minutes} min</span>
                        </div>
                      </TableCell>

                      <TableCell>
                        <div className="text-xs font-semibold text-surface-900">
                          {shift.total_work_hours} hrs
                        </div>
                      </TableCell>

                      <TableCell>
                        {shift.status === 'ACTIVE' ? (
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
                                onClick={() => handleOpenEdit(shift)}
                                title="Editar turno"
                                className="h-8 w-8 p-0 text-surface-500 hover:text-surface-800"
                              >
                                <Edit2 className="w-4 h-4" />
                              </Button>

                              <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => setShiftToDelete(shift)}
                                title="Eliminar turno"
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

      <ShiftTypeModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        shiftToEdit={shiftToEdit}
        onSubmit={handleFormSubmit}
        isLoading={isCreating || isUpdating}
      />

      <ConfirmDialog
        isOpen={!!shiftToDelete}
        onClose={() => setShiftToDelete(null)}
        onConfirm={() => {
          if (shiftToDelete) {
            deleteShiftType(shiftToDelete.id, {
              onSuccess: () => setShiftToDelete(null),
            });
          }
        }}
        title="¿Eliminar tipo de turno?"
        message={`¿Está seguro de que desea eliminar el tipo de turno '${shiftToDelete?.name}' [${shiftToDelete?.code}]? Esta acción solo es posible si no se encuentra asignado en mallas de horarios.`}
        confirmText="Eliminar Turno"
        variant="danger"
        isLoading={isDeleting}
      />
    </div>
  );
};
