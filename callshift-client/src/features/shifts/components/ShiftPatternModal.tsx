import React, { useState, useEffect } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { Textarea } from '@/components/forms/Textarea';
import { ShiftPattern, ShiftPatternEntry } from '@/types/shiftPattern';
import { useShiftTypes } from '@/features/shifts/hooks/useShiftTypes';
import { useDepartments } from '@/features/organization/hooks/useDepartments';
import { usePositions } from '@/features/organization/hooks/usePositions';
import { Sparkles, Calendar, Clock, CheckCircle2 } from 'lucide-react';

interface ShiftPatternModalProps {
  isOpen: boolean;
  onClose: () => void;
  onSave: (data: Partial<ShiftPattern>) => Promise<void>;
  pattern?: ShiftPattern | null;
  isLoading?: boolean;
}

export const ShiftPatternModal: React.FC<ShiftPatternModalProps> = ({
  isOpen,
  onClose,
  onSave,
  pattern,
  isLoading = false,
}) => {
  const { compactShiftTypes: shiftTypes } = useShiftTypes();
  const { compactDepartments: departments } = useDepartments();
  const { compactPositions: positions } = usePositions();

  const [name, setName] = useState('');
  const [code, setCode] = useState('');
  const [cycleLength, setCycleLength] = useState(7);
  const [departmentId, setDepartmentId] = useState<number | undefined>();
  const [positionId, setPositionId] = useState<number | undefined>();
  const [description, setDescription] = useState('');
  const [status, setStatus] = useState<'ACTIVE' | 'INACTIVE'>('ACTIVE');
  const [entries, setEntries] = useState<ShiftPatternEntry[]>([]);
  const [error, setError] = useState<string | null>(null);

  // Inicializar estado al abrir modal o cambiar pattern
  useEffect(() => {
    if (pattern) {
      setName(pattern.name);
      setCode(pattern.code);
      setCycleLength(pattern.cycle_length_days);
      setDepartmentId(pattern.department_id || undefined);
      setPositionId(pattern.position_id || undefined);
      setDescription(pattern.description || '');
      setStatus(pattern.status);
      setEntries(
        pattern.entries && pattern.entries.length > 0
          ? pattern.entries.map((e) => ({ ...e }))
          : generateDefaultEntries(pattern.cycle_length_days)
      );
    } else {
      setName('');
      setCode('');
      setCycleLength(7);
      setDepartmentId(undefined);
      setPositionId(undefined);
      setDescription('');
      setStatus('ACTIVE');
      setEntries(generateDefaultEntries(7));
    }
    setError(null);
  }, [pattern, isOpen]);

  // Genera entradas por defecto para N días
  function generateDefaultEntries(length: number, defaultShiftId?: number): ShiftPatternEntry[] {
    const list: ShiftPatternEntry[] = [];
    const firstShiftId = defaultShiftId || (shiftTypes.length > 0 ? shiftTypes[0].id : null);

    for (let d = 1; d <= length; d++) {
      const isWeekend = d === 6 || d === 7;
      list.push({
        day_number: d,
        day_type: isWeekend && length === 7 ? 'REST' : 'WORK',
        shift_type_id: isWeekend && length === 7 ? null : firstShiftId,
        notes: null,
      });
    }
    return list;
  }

  // Ajusta la longitud del ciclo
  const handleCycleLengthChange = (newLen: number) => {
    if (newLen < 1 || newLen > 365) return;
    setCycleLength(newLen);

    const firstShiftId = shiftTypes.length > 0 ? shiftTypes[0].id : null;
    const current = [...entries];

    if (newLen > current.length) {
      for (let d = current.length + 1; d <= newLen; d++) {
        current.push({
          day_number: d,
          day_type: 'WORK',
          shift_type_id: firstShiftId,
          notes: null,
        });
      }
    } else if (newLen < current.length) {
      current.splice(newLen);
    }

    setEntries(current);
  };

  // Presets rápidos
  const applyPreset = (preset: '5x2' | '6x1' | '4x2') => {
    const firstShiftId = shiftTypes.length > 0 ? shiftTypes[0].id : null;
    const secondShiftId = shiftTypes.length > 1 ? shiftTypes[1].id : firstShiftId;

    if (preset === '5x2') {
      setCycleLength(7);
      setName((prev) => prev || 'Ciclo Estándar 5x2');
      setCode((prev) => prev || 'PAT-5X2');
      const list: ShiftPatternEntry[] = [];
      for (let d = 1; d <= 5; d++) {
        list.push({ day_number: d, day_type: 'WORK', shift_type_id: firstShiftId });
      }
      list.push({ day_number: 6, day_type: 'REST', shift_type_id: null });
      list.push({ day_number: 7, day_type: 'REST', shift_type_id: null });
      setEntries(list);
    } else if (preset === '6x1') {
      setCycleLength(7);
      setName((prev) => prev || 'Ciclo Continuo 6x1');
      setCode((prev) => prev || 'PAT-6X1');
      const list: ShiftPatternEntry[] = [];
      for (let d = 1; d <= 6; d++) {
        list.push({ day_number: d, day_type: 'WORK', shift_type_id: firstShiftId });
      }
      list.push({ day_number: 7, day_type: 'REST', shift_type_id: null });
      setEntries(list);
    } else if (preset === '4x2') {
      setCycleLength(6);
      setName((prev) => prev || 'Ciclo Rotativo 4x2');
      setCode((prev) => prev || 'PAT-4X2');
      setEntries([
        { day_number: 1, day_type: 'WORK', shift_type_id: firstShiftId },
        { day_number: 2, day_type: 'WORK', shift_type_id: firstShiftId },
        { day_number: 3, day_type: 'WORK', shift_type_id: secondShiftId },
        { day_number: 4, day_type: 'WORK', shift_type_id: secondShiftId },
        { day_number: 5, day_type: 'REST', shift_type_id: null },
        { day_number: 6, day_type: 'REST', shift_type_id: null },
      ]);
    }
  };

  const handleEntryChange = (
    index: number,
    field: keyof ShiftPatternEntry,
    value: any
  ) => {
    const updated = [...entries];
    updated[index] = { ...updated[index], [field]: value };

    // Si cambia a descanso, limpiar shift_type_id
    if (field === 'day_type' && value !== 'WORK') {
      updated[index].shift_type_id = null;
    } else if (field === 'day_type' && value === 'WORK' && !updated[index].shift_type_id) {
      updated[index].shift_type_id = shiftTypes.length > 0 ? shiftTypes[0].id : null;
    }

    setEntries(updated);
  };

  // Cálculos resumen
  const workDaysCount = entries.filter((e) => e.day_type === 'WORK').length;
  const restDaysCount = entries.filter((e) => e.day_type !== 'WORK').length;
  const totalWorkHours = entries.reduce((acc, curr) => {
    if (curr.day_type === 'WORK' && curr.shift_type_id) {
      const st = shiftTypes.find((s) => s.id === Number(curr.shift_type_id));
      return acc + (st ? Number(st.total_work_hours) : 0);
    }
    return acc;
  }, 0);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);

    if (!name.trim()) {
      setError('El nombre del patrón es obligatorio.');
      return;
    }
    if (!code.trim()) {
      setError('El código del patrón es obligatorio.');
      return;
    }
    if (entries.length !== cycleLength) {
      setError(`Debe configurar exactamente ${cycleLength} días.`);
      return;
    }

    for (let i = 0; i < entries.length; i++) {
      const item = entries[i];
      if (item.day_type === 'WORK' && !item.shift_type_id) {
        setError(`El día ${item.day_number} es de tipo LABORAL pero no tiene turno seleccionado.`);
        return;
      }
    }

    try {
      await onSave({
        name: name.trim(),
        code: code.trim().toUpperCase(),
        cycle_length_days: cycleLength,
        department_id: departmentId || null,
        position_id: positionId || null,
        description: description.trim() || null,
        status,
        entries: entries.map((e, idx) => ({
          day_number: idx + 1,
          day_type: e.day_type,
          shift_type_id: e.day_type === 'WORK' ? Number(e.shift_type_id) : null,
          notes: e.notes || null,
        })),
      });
      onClose();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al guardar el patrón de turno.');
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={pattern ? 'Editar Patrón de Turno' : 'Nuevo Patrón de Turno Cíclico'}
      size="xl"
    >
      <form onSubmit={handleSubmit} className="space-y-6">
        {error && (
          <div className="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {error}
          </div>
        )}

        {/* Plantillas / Presets rápidos */}
        {!pattern && (
          <div className="bg-brand-50/50 dark:bg-brand-950/20 border border-brand-200 dark:border-brand-800/40 rounded-xl p-4">
            <div className="flex items-center gap-2 mb-2 text-xs font-semibold uppercase tracking-wider text-brand-700 dark:text-brand-300">
              <Sparkles className="w-4 h-4" />
              <span>Plantillas rápidas recomendadas</span>
            </div>
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => applyPreset('5x2')}
                className="text-xs bg-white dark:bg-gray-800"
              >
                5x2 Estándar (5 Lab / 2 Desc)
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => applyPreset('6x1')}
                className="text-xs bg-white dark:bg-gray-800"
              >
                6x1 Continuo (6 Lab / 1 Desc)
              </Button>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => applyPreset('4x2')}
                className="text-xs bg-white dark:bg-gray-800"
              >
                4x2 Rotativo (2M + 2N + 2D)
              </Button>
            </div>
          </div>
        )}

        {/* Datos Principales */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Input
            label="Nombre del Patrón"
            placeholder="ej. Rotativo 5x2 Mañana"
            value={name}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setName(e.target.value)}
            required
          />
          <Input
            label="Código Único"
            placeholder="ej. PAT-5X2-M"
            value={code}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setCode(e.target.value.toUpperCase())}
            required
          />
          <div>
            <label className="block text-xs font-semibold uppercase tracking-wider text-surface-700 mb-1">
              Días en el Ciclo
            </label>
            <input
              type="number"
              min="1"
              max="365"
              value={cycleLength}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => handleCycleLengthChange(parseInt(e.target.value) || 1)}
              className="w-full px-3.5 py-2 border border-surface-300 rounded-lg bg-white text-surface-900 focus:ring-2 focus:ring-brand-500 focus:border-brand-500 text-sm"
              required
            />
          </div>
        </div>

        {/* Alcance Organizacional y Estado */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Select
            label="Departamento (Opcional)"
            value={departmentId ? String(departmentId) : ''}
            onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setDepartmentId(e.target.value ? Number(e.target.value) : undefined)}
            options={[
              { value: '', label: 'Toda la Empresa' },
              ...departments.map((dept) => ({
                value: String(dept.id),
                label: `${dept.name} (${dept.code})`,
              })),
            ]}
          />

          <Select
            label="Cargo (Opcional)"
            value={positionId ? String(positionId) : ''}
            onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setPositionId(e.target.value ? Number(e.target.value) : undefined)}
            options={[
              { value: '', label: 'Todos los Cargos' },
              ...positions.map((pos) => ({
                value: String(pos.id),
                label: `${pos.name} (${pos.code})`,
              })),
            ]}
          />

          <Select
            label="Estado"
            value={status}
            onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setStatus(e.target.value as 'ACTIVE' | 'INACTIVE')}
            options={[
              { value: 'ACTIVE', label: 'Activo' },
              { value: 'INACTIVE', label: 'Inactivo' },
            ]}
          />
        </div>

        <Textarea
          label="Descripción o Reglas del Patrón"
          placeholder="Anotaciones sobre rotación, descansos compensatorios o alcance..."
          value={description}
          onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setDescription(e.target.value)}
          rows={2}
        />

        {/* Resumen Métrico del Ciclo */}
        <div className="flex flex-wrap items-center justify-between p-3 bg-gray-50 dark:bg-gray-800/60 rounded-xl border border-gray-200 dark:border-gray-700/60 text-xs">
          <div className="flex items-center gap-4">
            <span className="font-semibold text-gray-700 dark:text-gray-300">
              Longitud: <span className="text-brand-600 font-bold">{cycleLength} días</span>
            </span>
            <span className="text-gray-500 dark:text-gray-400">
              Laborables: <strong className="text-gray-900 dark:text-gray-100">{workDaysCount}</strong>
            </span>
            <span className="text-gray-500 dark:text-gray-400">
              Descansos: <strong className="text-gray-900 dark:text-gray-100">{restDaysCount}</strong>
            </span>
          </div>
          <div className="flex items-center gap-1 font-semibold text-brand-700 dark:text-brand-300">
            <Clock className="w-3.5 h-3.5" />
            <span>Total horas del ciclo: {totalWorkHours.toFixed(1)} hrs</span>
          </div>
        </div>

        {/* Secuencia Cíclica de Días */}
        <div className="space-y-3">
          <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100 flex items-center gap-2">
            <Calendar className="w-4 h-4 text-brand-600" />
            <span>Secuencia Cíclica de Turnos (Día 1 a {cycleLength})</span>
          </h4>

          <div className="max-h-72 overflow-y-auto border border-gray-200 dark:border-gray-800 rounded-xl divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
            {entries.map((entry, idx) => (
              <div
                key={idx}
                className="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3 hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors"
              >
                <div className="flex items-center gap-3 min-w-28">
                  <span className="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-gray-100 dark:bg-gray-800 font-bold text-xs text-gray-700 dark:text-gray-300">
                    D{entry.day_number}
                  </span>
                  <select
                    value={entry.day_type}
                    onChange={(e: React.ChangeEvent<HTMLSelectElement>) => handleEntryChange(idx, 'day_type', e.target.value)}
                    className="text-xs font-semibold px-2.5 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                  >
                    <option value="WORK">Laboral</option>
                    <option value="REST">Descanso</option>
                    <option value="OFF">Día Libre</option>
                    <option value="HOLIDAY">Feriado</option>
                  </select>
                </div>

                {entry.day_type === 'WORK' ? (
                  <div className="flex-1 flex items-center gap-3">
                    <select
                      value={entry.shift_type_id ? String(entry.shift_type_id) : ''}
                      onChange={(e: React.ChangeEvent<HTMLSelectElement>) =>
                        handleEntryChange(idx, 'shift_type_id', e.target.value ? Number(e.target.value) : null)
                      }
                      className="w-full text-xs px-3 py-1.5 rounded-lg border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100"
                    >
                      <option value="">-- Seleccione Turno --</option>
                      {shiftTypes.map((st) => (
                        <option key={st.id} value={st.id}>
                          {st.name} ({st.code}) — {st.start_time.slice(0, 5)} a {st.end_time.slice(0, 5)} (
                          {st.total_work_hours}h)
                        </option>
                      ))}
                    </select>
                  </div>
                ) : (
                  <div className="flex-1 text-xs text-gray-400 dark:text-gray-500 italic flex items-center gap-1.5">
                    <CheckCircle2 className="w-3.5 h-3.5 text-emerald-500" />
                    <span>Día no laboral programado ({entry.day_type})</span>
                  </div>
                )}
              </div>
            ))}
          </div>
        </div>

        {/* Botones de acción */}
        <div className="flex justify-end gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
          <Button type="button" variant="secondary" onClick={onClose} disabled={isLoading}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" isLoading={isLoading}>
            {pattern ? 'Guardar Cambios' : 'Crear Patrón'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
