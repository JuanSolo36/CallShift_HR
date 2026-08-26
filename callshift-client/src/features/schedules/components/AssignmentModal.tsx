import React, { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/forms/Input';
import { Clock, Trash2, Calendar, User, Check } from 'lucide-react';
import type {
  ScheduleGridEmployee,
  ScheduleShiftTypeItem,
  ScheduleAssignmentItem,
  UpsertAssignmentPayload,
} from '@/types/schedule.types';

interface AssignmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  employee: ScheduleGridEmployee | null;
  date: string;
  formattedDate: string;
  currentAssignment: ScheduleAssignmentItem | null;
  shiftTypes: ScheduleShiftTypeItem[];
  lockVersion: number;
  onSave: (payload: UpsertAssignmentPayload) => void;
  onDelete?: (assignmentId: number) => void;
  isLoading?: boolean;
}

export const AssignmentModal: React.FC<AssignmentModalProps> = ({
  isOpen,
  onClose,
  employee,
  date,
  formattedDate,
  currentAssignment,
  shiftTypes,
  lockVersion,
  onSave,
  onDelete,
  isLoading = false,
}) => {
  const [selectedShiftId, setSelectedShiftId] = useState<number | null>(null);
  const [dayType, setDayType] = useState<'WORK' | 'REST' | 'OFF' | 'HOLIDAY' | 'PERMISSION' | 'ABSENCE'>('WORK');
  const [notes, setNotes] = useState('');

  useEffect(() => {
    if (isOpen) {
      if (currentAssignment) {
        setSelectedShiftId(currentAssignment.shift_type_id || null);
        setDayType(currentAssignment.day_type || 'WORK');
        setNotes(currentAssignment.notes || '');
      } else {
        setSelectedShiftId(shiftTypes.length > 0 ? shiftTypes[0].id : null);
        setDayType('WORK');
        setNotes('');
      }
    }
  }, [isOpen, currentAssignment, shiftTypes]);

  if (!employee) return null;

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSave({
      employee_id: employee.id,
      date,
      day_type: dayType,
      shift_type_id: dayType === 'WORK' ? selectedShiftId : null,
      lock_version: lockVersion,
      notes: notes.trim() || undefined,
    });
  };

  const dayTypeOptions: { value: typeof dayType; label: string; color: string }[] = [
    { value: 'WORK', label: 'Jornada Laboral (Turno)', color: 'border-brand-500 bg-brand-50/50' },
    { value: 'REST', label: 'Descanso Programado', color: 'border-emerald-500 bg-emerald-50/50' },
    { value: 'OFF', label: 'Día Libre / No Laboral', color: 'border-surface-400 bg-surface-100' },
    { value: 'HOLIDAY', label: 'Festivo Oficial', color: 'border-amber-500 bg-amber-50/50' },
    { value: 'PERMISSION', label: 'Permiso Especial', color: 'border-indigo-500 bg-indigo-50/50' },
    { value: 'ABSENCE', label: 'Ausencia / Incapacidad', color: 'border-rose-500 bg-rose-50/50' },
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Asignar Turno en Celda"
      size="md"
    >
      <form onSubmit={handleSubmit} className="space-y-4 text-left">
        {/* Contexto del Colaborador y Fecha */}
        <div className="bg-surface-50 p-3 rounded-lg border border-surface-200 grid grid-cols-2 gap-2 text-xs">
          <div className="flex items-center gap-1.5 text-surface-800">
            <User className="w-4 h-4 text-brand-600 shrink-0" />
            <span className="font-semibold truncate">{employee.full_name}</span>
          </div>
          <div className="flex items-center gap-1.5 text-surface-800 justify-end">
            <Calendar className="w-4 h-4 text-surface-500 shrink-0" />
            <span className="font-mono font-medium">{date} ({formattedDate})</span>
          </div>
        </div>

        {/* Selector de Tipo de Día */}
        <div>
          <label className="block text-xs font-semibold text-surface-700 mb-1.5">
            Tipo de Jornada
          </label>
          <div className="grid grid-cols-2 sm:grid-cols-3 gap-2">
            {dayTypeOptions.map((opt) => (
              <button
                key={opt.value}
                type="button"
                onClick={() => setDayType(opt.value)}
                className={`px-2.5 py-1.5 text-xs font-medium rounded border transition-all text-left flex items-center justify-between ${
                  dayType === opt.value
                    ? `${opt.color} ring-1 ring-brand-500 text-surface-900 font-semibold shadow-xs`
                    : 'border-surface-200 hover:border-surface-300 text-surface-600 bg-white'
                }`}
              >
                <span>{opt.label}</span>
                {dayType === opt.value && <Check className="w-3.5 h-3.5 text-brand-600 shrink-0 ml-1" />}
              </button>
            ))}
          </div>
        </div>

        {/* Selector de Turnos (Solo si dayType === 'WORK') */}
        {dayType === 'WORK' && (
          <div className="space-y-2">
            <label className="block text-xs font-semibold text-surface-700">
              Seleccione el Turno Disponible
            </label>
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-56 overflow-y-auto p-1">
              {shiftTypes.map((shift) => {
                const isSelected = selectedShiftId === shift.id;
                return (
                  <button
                    key={shift.id}
                    type="button"
                    onClick={() => setSelectedShiftId(shift.id)}
                    className={`p-2.5 rounded-lg border text-left flex items-center gap-2.5 transition-all ${
                      isSelected
                        ? 'border-brand-500 bg-brand-50/40 ring-1 ring-brand-500 shadow-xs'
                        : 'border-surface-200 hover:border-surface-300 bg-white'
                    }`}
                  >
                    <span
                      className="w-3.5 h-3.5 rounded-full shrink-0 shadow-xs"
                      style={{ backgroundColor: shift.color_hex }}
                    />
                    <div className="flex-1 min-w-0">
                      <div className="flex items-center justify-between">
                        <span className="font-semibold text-xs text-surface-900 truncate">
                          {shift.name}
                        </span>
                        <span className="font-mono text-[10px] text-surface-500 ml-1">
                          {shift.code}
                        </span>
                      </div>
                      <div className="text-[11px] text-surface-500 flex items-center gap-1.5 mt-0.5 font-mono">
                        <Clock className="w-3 h-3 text-surface-400" />
                        <span>
                          {shift.start_time} - {shift.end_time}
                        </span>
                        {shift.crosses_midnight && (
                          <span className="text-[9px] bg-purple-100 text-purple-700 px-1 rounded">
                            +1d
                          </span>
                        )}
                        <span className="text-[10px] font-sans text-surface-400">
                          ({shift.total_work_hours}h)
                        </span>
                      </div>
                    </div>
                  </button>
                );
              })}
            </div>
          </div>
        )}

        <Input
          label="Observaciones de Asignación (Opcional)"
          placeholder="Ej. Reemplazo temporal en puesto de atención..."
          value={notes}
          onChange={(e) => setNotes(e.target.value)}
        />

        <div className="flex items-center justify-between pt-3 border-t border-surface-100">
          <div>
            {currentAssignment && onDelete && (
              <Button
                type="button"
                variant="ghost"
                size="sm"
                className="text-rose-600 hover:bg-rose-50"
                leftIcon={<Trash2 className="w-3.5 h-3.5" />}
                onClick={() => onDelete(currentAssignment.id)}
                disabled={isLoading}
              >
                Liberar Turno
              </Button>
            )}
          </div>

          <div className="flex items-center gap-2">
            <Button type="button" variant="secondary" size="sm" onClick={onClose} disabled={isLoading}>
              Cancelar
            </Button>
            <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
              Guardar en Malla
            </Button>
          </div>
        </div>
      </form>
    </Modal>
  );
};
