import React, { useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { useDepartments } from '@/features/organization/hooks/useDepartments';
import { CalendarRange, Clock } from 'lucide-react';
import type {
  WorkPeriodItem,
  CreateWorkPeriodPayload,
  UpdateWorkPeriodPayload,
  WorkPeriodType,
} from '@/types/workPeriod.types';

const workPeriodSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'Máximo 100 caracteres.'),
  period_type: z.enum(['WEEKLY', 'BIWEEKLY', 'MONTHLY', 'CUSTOM']),
  department_id: z.coerce.number().optional().nullable(),
  start_date: z.string().min(8, 'Fecha de inicio requerida.'),
  end_date: z.string().min(8, 'Fecha de fin requerida.'),
}).refine((data) => {
  return new Date(data.start_date) <= new Date(data.end_date);
}, {
  message: 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
  path: ['end_date'],
});

type WorkPeriodFormData = z.infer<typeof workPeriodSchema>;

interface WorkPeriodModalProps {
  isOpen: boolean;
  onClose: () => void;
  periodToEdit?: WorkPeriodItem | null;
  onSubmit: (payload: CreateWorkPeriodPayload | UpdateWorkPeriodPayload) => void;
  isLoading?: boolean;
}

export const WorkPeriodModal: React.FC<WorkPeriodModalProps> = ({
  isOpen,
  onClose,
  periodToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const isEditing = !!periodToEdit;
  const { compactDepartments } = useDepartments();

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors },
  } = useForm<WorkPeriodFormData>({
    resolver: zodResolver(workPeriodSchema),
    defaultValues: {
      name: '',
      period_type: 'WEEKLY',
      department_id: null,
      start_date: new Date().toISOString().split('T')[0],
      end_date: new Date(Date.now() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
    },
  });

  const startDate = watch('start_date');
  const endDate = watch('end_date');
  const periodType = watch('period_type');

  // Cálculo en tiempo real de días comprendidos
  const durationDays = useMemo(() => {
    if (!startDate || !endDate) return 0;
    const start = new Date(startDate);
    const end = new Date(endDate);
    if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) return 0;
    const diffTime = end.getTime() - start.getTime();
    return Math.round(diffTime / (1000 * 3600 * 24)) + 1;
  }, [startDate, endDate]);

  useEffect(() => {
    if (isOpen) {
      if (periodToEdit) {
        reset({
          name: periodToEdit.name,
          period_type: periodToEdit.period_type,
          department_id: periodToEdit.department_id || null,
          start_date: periodToEdit.start_date,
          end_date: periodToEdit.end_date,
        });
      } else {
        const today = new Date();
        const start = today.toISOString().split('T')[0];
        const end = new Date(Date.now() + 6 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        reset({
          name: `Semana ${getWeekNumber(today)} - Planificación`,
          period_type: 'WEEKLY',
          department_id: null,
          start_date: start,
          end_date: end,
        });
      }
    }
  }, [isOpen, periodToEdit, reset]);

  const onFormSubmit = (data: WorkPeriodFormData) => {
    const payload: CreateWorkPeriodPayload | UpdateWorkPeriodPayload = {
      name: data.name,
      period_type: data.period_type as WorkPeriodType,
      department_id: data.department_id ? Number(data.department_id) : null,
      start_date: data.start_date,
      end_date: data.end_date,
      lock_version: periodToEdit?.current_version?.lock_version,
    };

    onSubmit(payload);
  };

  const handlePeriodTypeChange = (type: WorkPeriodType) => {
    setValue('period_type', type);
    const start = new Date(startDate || new Date());
    let daysToAdd = 6; // WEEKLY default (7 days inclusive)

    if (type === 'BIWEEKLY') daysToAdd = 14;
    else if (type === 'MONTHLY') daysToAdd = 29;
    else if (type === 'CUSTOM') return;

    const end = new Date(start.getTime() + daysToAdd * 24 * 60 * 60 * 1000);
    setValue('end_date', end.toISOString().split('T')[0]);
  };

  const periodTypeOptions = [
    { value: 'WEEKLY', label: 'Semanal (7 días)' },
    { value: 'BIWEEKLY', label: 'Quincenal (15 días)' },
    { value: 'MONTHLY', label: 'Mensual (30 días)' },
    { value: 'CUSTOM', label: 'Personalizado' },
  ];

  const departmentOptions = [
    { value: '', label: 'Toda la Empresa (Global / Transversal)' },
    ...compactDepartments.map((d) => ({ value: String(d.id), label: `${d.name} (${d.code})` })),
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? `Editar Periodo: ${periodToEdit.name}` : 'Crear Nuevo Periodo Laboral'}
      size="md"
    >
      <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-4 text-left">
        <Input
          label="Nombre o Etiqueta del Periodo"
          required
          placeholder="Ej. Semana 35 - Operaciones y Soporte"
          error={errors.name?.message}
          {...register('name')}
        />

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Select
            label="Tipo de Intervalo"
            options={periodTypeOptions}
            value={periodType}
            onChange={(e) => handlePeriodTypeChange(e.target.value as WorkPeriodType)}
            error={errors.period_type?.message}
          />

          <Select
            label="Departamento o Área"
            options={departmentOptions}
            error={errors.department_id?.message}
            {...register('department_id')}
          />
        </div>

        {/* Fechas y cálculo de duración */}
        <div className="bg-surface-50 p-3.5 rounded-lg border border-surface-200/80 space-y-3">
          <div className="text-xs font-semibold text-surface-800 flex items-center justify-between">
            <div className="flex items-center gap-1.5">
              <CalendarRange className="w-3.5 h-3.5 text-brand-600" />
              <span>Rango Temporal Inclusivo</span>
            </div>
            {durationDays > 0 && (
              <Badge variant="brand" size="sm" className="gap-1">
                <Clock className="w-3 h-3" /> {durationDays} {durationDays === 1 ? 'día' : 'días'} de planificación
              </Badge>
            )}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <Input
              label="Fecha de Inicio"
              type="date"
              required
              error={errors.start_date?.message}
              {...register('start_date')}
            />

            <Input
              label="Fecha de Fin"
              type="date"
              required
              error={errors.end_date?.message}
              {...register('end_date')}
            />
          </div>
        </div>

        {isEditing && periodToEdit.current_version && (
          <div className="flex items-center justify-between p-2.5 bg-brand-50/50 rounded border border-brand-100 text-xs text-brand-900">
            <span>Versión activa de planificación:</span>
            <span className="font-mono font-semibold">
              V{periodToEdit.current_version.version_number} (lock #{periodToEdit.current_version.lock_version})
            </span>
          </div>
        )}

        <div className="flex items-center justify-end gap-2 pt-4 border-t border-surface-100">
          <Button type="button" variant="secondary" size="sm" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
            {isEditing ? 'Guardar Cambios' : 'Crear Periodo Laboral'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};

function getWeekNumber(d: Date): number {
  const date = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
  const dayNum = date.getUTCDay() || 7;
  date.setUTCDate(date.getUTCDate() + 4 - dayNum);
  const yearStart = new Date(Date.UTC(date.getUTCFullYear(), 0, 1));
  return Math.ceil(((date.getTime() - yearStart.getTime()) / 86400000 + 1) / 7);
}
