import React, { useEffect, useMemo } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Textarea } from '@/components/forms/Textarea';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Clock, Moon, Sun } from 'lucide-react';
import type {
  ShiftTypeItem,
  CreateShiftTypePayload,
  UpdateShiftTypePayload,
} from '@/types/shiftType.types';

const shiftTypeSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(80, 'Máximo 80 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  color_hex: z.string().regex(/^#([a-fA-F0-9]{6})$/, 'Color hexadecimal inválido (ej. #3B82F6).'),
  start_time: z.string().min(4, 'Hora de inicio requerida.'),
  end_time: z.string().min(4, 'Hora de fin requerida.'),
  break_duration_minutes: z.coerce.number().min(0, 'Mínimo 0 minutos.').max(360, 'Máximo 360 minutos.'),
  total_work_hours: z.coerce.number().optional().nullable(),
  crosses_midnight: z.boolean().optional(),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

type ShiftTypeFormData = z.infer<typeof shiftTypeSchema>;

interface ShiftTypeModalProps {
  isOpen: boolean;
  onClose: () => void;
  shiftToEdit?: ShiftTypeItem | null;
  onSubmit: (payload: CreateShiftTypePayload | UpdateShiftTypePayload) => void;
  isLoading?: boolean;
}

const PRESET_COLORS = [
  '#3B82F6', // Azul
  '#10B981', // Esmeralda
  '#F59E0B', // Ámbar
  '#6366F1', // Índigo
  '#EC4899', // Rosa
  '#8B5CF6', // Púrpura
  '#14B8A6', // Turquesa
  '#EF4444', // Rojo
  '#64748B', // Pizarra
];

export const ShiftTypeModal: React.FC<ShiftTypeModalProps> = ({
  isOpen,
  onClose,
  shiftToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const isEditing = !!shiftToEdit;

  const {
    register,
    handleSubmit,
    reset,
    watch,
    setValue,
    formState: { errors },
  } = useForm<ShiftTypeFormData>({
    resolver: zodResolver(shiftTypeSchema),
    defaultValues: {
      name: '',
      code: '',
      color_hex: '#3B82F6',
      start_time: '08:00',
      end_time: '17:00',
      break_duration_minutes: 60,
      total_work_hours: null,
      crosses_midnight: false,
      description: '',
      status: 'ACTIVE',
    },
  });

  const startTime = watch('start_time');
  const endTime = watch('end_time');
  const breakMinutes = watch('break_duration_minutes') ?? 60;
  const currentColor = watch('color_hex') || '#3B82F6';

  // Cálculo en tiempo real de jornada y cruce de medianoche
  const { crossesMidnight, calculatedHours, rawHours } = useMemo(() => {
    if (!startTime || !endTime) {
      return { crossesMidnight: false, calculatedHours: 0, rawHours: 0 };
    }

    const [sh, sm] = startTime.split(':').map(Number);
    const [eh, em] = endTime.split(':').map(Number);

    if (isNaN(sh) || isNaN(sm) || isNaN(eh) || isNaN(em)) {
      return { crossesMidnight: false, calculatedHours: 0, rawHours: 0 };
    }

    const startM = sh * 60 + sm;
    const endM = eh * 60 + em;

    let crosses = false;
    let rawM = 0;

    if (endM < startM) {
      crosses = true;
      rawM = (1440 - startM) + endM;
    } else if (endM > startM) {
      crosses = false;
      rawM = endM - startM;
    } else {
      // Mismo inicio y fin
      crosses = true;
      rawM = 1440; // 24h
    }

    const effectiveM = Math.max(0, rawM - (Number(breakMinutes) || 0));
    const hours = Number((effectiveM / 60).toFixed(2));
    const totalRaw = Number((rawM / 60).toFixed(2));

    return { crossesMidnight: crosses, calculatedHours: hours, rawHours: totalRaw };
  }, [startTime, endTime, breakMinutes]);

  useEffect(() => {
    if (isOpen) {
      if (shiftToEdit) {
        reset({
          name: shiftToEdit.name,
          code: shiftToEdit.code,
          color_hex: shiftToEdit.color_hex || '#3B82F6',
          start_time: shiftToEdit.start_time.substring(0, 5),
          end_time: shiftToEdit.end_time.substring(0, 5),
          break_duration_minutes: shiftToEdit.break_duration_minutes,
          total_work_hours: shiftToEdit.total_work_hours,
          crosses_midnight: shiftToEdit.crosses_midnight,
          description: shiftToEdit.description || '',
          status: shiftToEdit.status,
        });
      } else {
        reset({
          name: '',
          code: '',
          color_hex: '#3B82F6',
          start_time: '08:00',
          end_time: '17:00',
          break_duration_minutes: 60,
          total_work_hours: null,
          crosses_midnight: false,
          description: '',
          status: 'ACTIVE',
        });
      }
    }
  }, [isOpen, shiftToEdit, reset]);

  const onFormSubmit = (data: ShiftTypeFormData) => {
    onSubmit({
      ...data,
      crosses_midnight: crossesMidnight,
      total_work_hours: calculatedHours,
      description: data.description || null,
    });
  };

  const statusOptions = [
    { value: 'ACTIVE', label: 'Activo' },
    { value: 'INACTIVE', label: 'Inactivo' },
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? `Editar Tipo de Turno: ${shiftToEdit.name}` : 'Nuevo Tipo de Turno'}
      size="md"
    >
      <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-4 text-left">
        <Input
          label="Nombre del Turno"
          required
          placeholder="Ej. Mañana Estándar (06:00 - 14:00)"
          error={errors.name?.message}
          {...register('name')}
        />

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            label="Código Identificador"
            required
            placeholder="Ej. M06_14"
            error={errors.code?.message}
            {...register('code')}
          />

          <Select
            label="Estado"
            options={statusOptions}
            error={errors.status?.message}
            {...register('status')}
          />
        </div>

        {/* Configuración de Horario y Descanso */}
        <div className="bg-surface-50 p-3.5 rounded-lg border border-surface-200/80 space-y-3">
          <div className="text-xs font-semibold text-surface-800 flex items-center gap-1.5">
            <Clock className="w-3.5 h-3.5 text-brand-600" />
            <span>Horarios y Duración de la Jornada</span>
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-3 gap-2.5">
            <Input
              label="Hora de Inicio"
              type="time"
              required
              error={errors.start_time?.message}
              {...register('start_time')}
            />

            <Input
              label="Hora de Fin"
              type="time"
              required
              error={errors.end_time?.message}
              {...register('end_time')}
            />

            <Input
              label="Descanso (minutos)"
              type="number"
              min="0"
              max="360"
              step="5"
              error={errors.break_duration_minutes?.message}
              {...register('break_duration_minutes')}
            />
          </div>

          {/* Indicadores en vivo */}
          <div className="pt-2 border-t border-surface-200/60 flex flex-wrap items-center justify-between gap-2 text-xs">
            <div className="flex items-center gap-2">
              {crossesMidnight ? (
                <Badge variant="warning" size="sm" className="gap-1">
                  <Moon className="w-3 h-3" /> Cruza Medianoche (D+1)
                </Badge>
              ) : (
                <Badge variant="neutral" size="sm" className="gap-1">
                  <Sun className="w-3 h-3 text-amber-500" /> Turno Diurno
                </Badge>
              )}
            </div>

            <div className="text-surface-700 font-medium">
              Brutas: <span className="font-semibold text-surface-900">{rawHours}h</span> | Efectivas:{' '}
              <span className="font-bold text-brand-600">{calculatedHours} hrs</span>
            </div>
          </div>
        </div>

        {/* Color en Malla de Horarios */}
        <div className="space-y-1.5">
          <label className="block text-xs font-medium text-surface-700">
            Color de Identificación en la Malla
          </label>
          <div className="flex items-center gap-2.5">
            <input
              type="color"
              className="w-9 h-9 p-0.5 rounded border border-surface-300 cursor-pointer"
              value={currentColor}
              onChange={(e) => setValue('color_hex', e.target.value)}
            />
            <div className="flex flex-wrap items-center gap-1.5">
              {PRESET_COLORS.map((hex) => (
                <button
                  key={hex}
                  type="button"
                  onClick={() => setValue('color_hex', hex)}
                  className={`w-6 h-6 rounded-full border-2 transition-transform hover:scale-110 ${
                    currentColor.toUpperCase() === hex.toUpperCase()
                      ? 'border-surface-900 scale-110'
                      : 'border-white'
                  }`}
                  style={{ backgroundColor: hex }}
                  title={hex}
                />
              ))}
            </div>
            <span className="text-xs font-mono text-surface-500">{currentColor}</span>
          </div>
          {errors.color_hex && (
            <p className="text-xs text-rose-600 mt-1">{errors.color_hex.message}</p>
          )}
        </div>

        <Textarea
          label="Descripción o Notas Operativas"
          placeholder="Especificaciones particulares para este turno..."
          rows={2}
          error={errors.description?.message}
          {...register('description')}
        />

        <div className="flex items-center justify-end gap-2 pt-4 border-t border-surface-100">
          <Button type="button" variant="secondary" size="sm" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
            {isEditing ? 'Guardar Cambios' : 'Crear Tipo de Turno'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
