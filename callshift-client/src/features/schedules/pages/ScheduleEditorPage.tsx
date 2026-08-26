import React, { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { PageHeader } from '@/components/layout/PageHeader';
import { Card, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { LoadingState } from '@/components/feedback/LoadingState';
import { EmptyState } from '@/components/feedback/EmptyState';
import { ScheduleGrid } from '../components/ScheduleGrid';
import { AssignmentModal } from '../components/AssignmentModal';
import { ApplyPatternModal } from '../components/ApplyPatternModal';
import { ConflictBadge } from '../components/ConflictBadge';
import { ConflictPanel } from '../components/ConflictPanel';
import { conflictService } from '@/services/conflictService';
import type { ScheduleConflict, ConflictValidationSummary } from '@/types/conflict';
import { useScheduleEditor } from '../hooks/useScheduleEditor';
import { useWorkPeriods } from '@/features/work-periods/hooks/useWorkPeriods';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  CalendarDays,
  Search,
  Building2,
  Calendar,
  Layers,
  RotateCw,
  ArrowLeft,
  Sparkles,
} from 'lucide-react';
import type {
  ScheduleGridEmployee,
  ScheduleGridDay,
  ScheduleAssignmentItem,
  UpsertAssignmentPayload,
} from '@/types/schedule.types';

export const ScheduleEditorPage: React.FC = () => {
  const { periodId } = useParams<{ periodId?: string }>();
  const navigate = useNavigate();
  const { hasPermission } = useAuthStore();

  const { compactWorkPeriods, isLoading: isLoadingPeriods } = useWorkPeriods();

  const selectedPeriodId = periodId ? Number(periodId) : compactWorkPeriods.length > 0 ? compactWorkPeriods[0].id : undefined;

  const {
    gridData,
    isLoading,
    isError,
    refetch,
    upsertAssignment,
    isUpserting,
    deleteAssignment,
    isDeleting,
  } = useScheduleEditor({
    workPeriodId: selectedPeriodId,
  });

  const [searchTerm, setSearchTerm] = useState('');
  const [selectedCell, setSelectedCell] = useState<{
    employee: ScheduleGridEmployee;
    day: ScheduleGridDay;
    assignment: ScheduleAssignmentItem | null;
  } | null>(null);

  const [isApplyPatternOpen, setIsApplyPatternOpen] = useState(false);
  const [isConflictPanelOpen, setIsConflictPanelOpen] = useState(false);
  const [conflicts, setConflicts] = useState<ScheduleConflict[]>([]);
  const [conflictSummary, setConflictSummary] = useState<ConflictValidationSummary | null>(null);
  const [isValidatingConflicts, setIsValidatingConflicts] = useState(false);

  const canEdit = (hasPermission('schedules:update') || hasPermission('schedules:create')) && !!gridData?.version?.is_editable;
  const canResolve = hasPermission('schedules:manage') || hasPermission('company:update');

  const handleValidateConflicts = async () => {
    if (!gridData?.version?.id) return;
    setIsValidatingConflicts(true);
    try {
      const response = await conflictService.validateVersion(gridData.version.id);
      setConflicts(response.data);
      setConflictSummary(response.summary);
    } catch (err) {
      console.error('Error al validar conflictos', err);
    } finally {
      setIsValidatingConflicts(false);
    }
  };

  const handlePeriodChange = (id: string) => {
    if (id) {
      navigate(`/schedules/${id}`);
    }
  };

  const handleCellClick = (
    employee: ScheduleGridEmployee,
    day: ScheduleGridDay,
    assignment: ScheduleAssignmentItem | null
  ) => {
    setSelectedCell({ employee, day, assignment });
  };

  const handleSaveAssignment = (payload: UpsertAssignmentPayload) => {
    if (!gridData) return;
    upsertAssignment(
      { versionId: gridData.version.id, payload },
      { onSuccess: () => setSelectedCell(null) }
    );
  };

  const handleDeleteAssignment = (assignmentId: number) => {
    if (!gridData) return;
    deleteAssignment(
      {
        versionId: gridData.version.id,
        assignmentId,
        payload: { lock_version: gridData.version.lock_version },
      },
      { onSuccess: () => setSelectedCell(null) }
    );
  };

  // Filtrar colaboradores por término de búsqueda
  const filteredGridData = gridData
    ? {
        ...gridData,
        employees: gridData.employees.filter((emp) => {
          const fullName = `${emp.first_name} ${emp.last_name}`.toLowerCase();
          const code = emp.employee_code.toLowerCase();
          const term = searchTerm.toLowerCase();
          return fullName.includes(term) || code.includes(term);
        }),
      }
    : null;

  const periodOptions = compactWorkPeriods.map((wp) => ({
    value: String(wp.id),
    label: `${wp.name} (${wp.start_date} → ${wp.end_date})`,
  }));

  if (isLoadingPeriods || (selectedPeriodId && isLoading && !gridData)) {
    return <LoadingState message="Cargando malla de horarios..." />;
  }

  if (compactWorkPeriods.length === 0) {
    return (
      <EmptyState
        icon={<CalendarDays className="w-6 h-6 stroke-[1.5]" />}
        title="No hay periodos de trabajo disponibles"
        description="Debe crear un periodo laboral antes de editar las asignaciones de horarios."
        actionText="Crear Periodo"
        onAction={() => navigate('/work-periods')}
      />
    );
  }

  return (
    <div className="space-y-4">
      {/* Encabezado */}
      <PageHeader
        title="Editor de Malla Horaria"
        description="Gestión y programación diaria de turnos por colaborador."
        actions={
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              leftIcon={<ArrowLeft className="w-3.5 h-3.5" />}
              onClick={() => navigate('/work-periods')}
            >
              Ver Periodos
            </Button>
            {gridData && (
              <ConflictBadge
                summary={conflictSummary}
                isLoading={isValidatingConflicts}
                onClick={() => {
                  if (!conflictSummary) {
                    handleValidateConflicts().then(() => setIsConflictPanelOpen(true));
                  } else {
                    setIsConflictPanelOpen(true);
                  }
                }}
              />
            )}
            {canEdit && (
              <Button
                variant="outline"
                size="sm"
                leftIcon={<Sparkles className="w-3.5 h-3.5 text-brand-600" />}
                onClick={() => setIsApplyPatternOpen(true)}
              >
                Aplicar Patrón
              </Button>
            )}
            <Button
              variant="secondary"
              size="sm"
              leftIcon={<RotateCw className="w-3.5 h-3.5" />}
              onClick={() => {
                refetch();
                if (conflictSummary) {
                  handleValidateConflicts();
                }
              }}
            >
              Sincronizar
            </Button>
          </div>
        }
      />

      {/* Barra de Filtros y Metadatos */}
      <Card>
        <CardContent className="p-3.5">
          <div className="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3">
            <div className="flex flex-1 items-center gap-3">
              <div className="w-72 min-w-[200px]">
                <Select
                  label=""
                  options={periodOptions}
                  value={selectedPeriodId ? String(selectedPeriodId) : ''}
                  onChange={(e: React.ChangeEvent<HTMLSelectElement>) => handlePeriodChange(e.target.value)}
                  placeholder="Seleccione un periodo..."
                />
              </div>

              <div className="w-64 min-w-[180px]">
                <Input
                  placeholder="Buscar colaborador..."
                  leftIcon={<Search className="w-4 h-4" />}
                  value={searchTerm}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
                />
              </div>
            </div>

            {gridData && (
              <div className="flex flex-wrap items-center gap-3 text-xs bg-surface-50 px-3 py-2 rounded-lg border border-surface-200/80">
                <div className="flex items-center gap-1.5 text-surface-800">
                  <Building2 className="w-3.5 h-3.5 text-surface-400" />
                  <span>{gridData.work_period.department?.name || 'Toda la Empresa'}</span>
                </div>

                <div className="flex items-center gap-1.5 text-surface-900 font-mono">
                  <Calendar className="w-3.5 h-3.5 text-surface-400" />
                  <span>
                    {gridData.work_period.start_date} → {gridData.work_period.end_date}
                  </span>
                </div>

                <div className="flex items-center gap-1.5">
                  <Layers className="w-3.5 h-3.5 text-brand-600" />
                  <Badge variant={gridData.version.is_editable ? 'brand' : 'neutral'} size="sm">
                    V{gridData.version.version_number} ({gridData.version.status})
                  </Badge>
                  <span className="font-mono text-[10px] text-surface-400">
                    lock #{gridData.version.lock_version}
                  </span>
                </div>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Leyenda de Turnos Disponibles */}
      {gridData && gridData.shift_types.length > 0 && (
        <div className="flex flex-wrap items-center gap-2 p-2.5 bg-white border border-surface-200/80 rounded-lg text-xs">
          <span className="text-[11px] font-bold text-surface-500 uppercase tracking-wider mr-1">
            Turnos:
          </span>
          {gridData.shift_types.map((shift) => (
            <div
              key={shift.id}
              className="flex items-center gap-1.5 px-2 py-0.5 rounded border border-surface-200 bg-surface-50 font-mono text-[11px]"
            >
              <span
                className="w-2.5 h-2.5 rounded-full"
                style={{ backgroundColor: shift.color_hex }}
              />
              <span className="font-semibold text-surface-800">{shift.code}</span>
              <span className="text-surface-500 text-[10px]">
                ({shift.start_time}-{shift.end_time})
              </span>
            </div>
          ))}

          <div className="h-3 w-px bg-surface-200 mx-1" />

          <div className="flex items-center gap-2 text-[11px] text-surface-600">
            <span className="px-1.5 py-0.5 rounded bg-surface-100 font-mono font-medium text-surface-500">
              LIBRE / DESC
            </span>
            <span className="text-surface-400">•</span>
            <span className="text-surface-400 italic">Click en celda para asignar</span>
          </div>
        </div>
      )}

      {/* Matriz de Horarios */}
      {filteredGridData ? (
        <ScheduleGrid
          gridData={filteredGridData}
          onCellClick={handleCellClick}
          isEditable={canEdit}
        />
      ) : isError ? (
        <EmptyState
          icon={<CalendarDays className="w-6 h-6 stroke-[1.5]" />}
          title="Error al cargar la malla"
          description="Ocurrió un error al obtener la información de asignaciones del periodo."
          actionText="Reintentar"
          onAction={() => refetch()}
        />
      ) : null}

      {/* Modal de Asignación Manual */}
      {selectedCell && gridData && (
        <AssignmentModal
          isOpen={!!selectedCell}
          onClose={() => setSelectedCell(null)}
          employee={selectedCell.employee}
          date={selectedCell.day.date}
          formattedDate={`${selectedCell.day.day_name} ${selectedCell.day.day_number}`}
          currentAssignment={selectedCell.assignment}
          shiftTypes={gridData.shift_types}
          lockVersion={gridData.version.lock_version}
          onSave={handleSaveAssignment}
          onDelete={handleDeleteAssignment}
          isLoading={isUpserting || isDeleting}
        />
      )}

      {/* Modal de Aplicación Masiva de Patrón */}
      {isApplyPatternOpen && gridData && (
        <ApplyPatternModal
          isOpen={isApplyPatternOpen}
          onClose={() => setIsApplyPatternOpen(false)}
          versionId={gridData.version.id}
          workPeriod={gridData.work_period}
          employees={gridData.employees}
          currentLockVersion={gridData.version.lock_version}
          onAppliedSuccess={() => {
            refetch();
            handleValidateConflicts();
          }}
        />
      )}

      {/* Drawer / Panel de Conflictos y Validación */}
      {gridData && (
        <ConflictPanel
          isOpen={isConflictPanelOpen}
          onClose={() => setIsConflictPanelOpen(false)}
          versionId={gridData.version.id}
          conflicts={conflicts}
          summary={conflictSummary}
          onRevalidate={handleValidateConflicts}
          onConflictResolved={() => {
            refetch();
          }}
          canResolve={canResolve}
        />
      )}
    </div>
  );
};
