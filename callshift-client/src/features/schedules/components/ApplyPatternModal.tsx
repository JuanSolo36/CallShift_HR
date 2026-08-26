import React, { useState } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { PatternPreviewResponse } from '@/types/shiftPattern';
import { ScheduleGridEmployee } from '@/types/schedule.types';
import { useShiftPatterns, usePatternApplication } from '@/features/shifts/hooks/useShiftPatterns';
import {
  Sparkles,
  Users,
  CheckCircle2,
  AlertTriangle,
  RefreshCw,
  Search,
} from 'lucide-react';

interface ApplyPatternModalProps {
  isOpen: boolean;
  onClose: () => void;
  versionId: number;
  workPeriod: {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    department_id?: number | null;
  };
  employees: ScheduleGridEmployee[];
  currentLockVersion: number;
  onAppliedSuccess?: () => void;
}

export const ApplyPatternModal: React.FC<ApplyPatternModalProps> = ({
  isOpen,
  onClose,
  versionId,
  workPeriod,
  employees,
  currentLockVersion,
  onAppliedSuccess,
}) => {
  const { patterns } = useShiftPatterns({ status: 'ACTIVE' });
  const { previewPattern, applyPattern, isPreviewing, isApplying } = usePatternApplication(versionId);

  const [selectedPatternId, setSelectedPatternId] = useState<number | undefined>();
  const [selectedEmployeeIds, setSelectedEmployeeIds] = useState<number[]>([]);
  const [startOffsetDay, setStartOffsetDay] = useState(1);
  const [startDate, setStartDate] = useState(workPeriod.start_date);
  const [endDate, setEndDate] = useState(workPeriod.end_date);
  const [empSearch, setEmpSearch] = useState('');

  const [previewResult, setPreviewResult] = useState<PatternPreviewResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  const selectedPattern = patterns.find((p) => p.id === Number(selectedPatternId));

  // Filtrar colaboradores
  const filteredEmployees = employees.filter((emp) => {
    const fullName = `${emp.first_name} ${emp.last_name}`.toLowerCase();
    const code = (emp.employee_code || '').toLowerCase();
    const term = empSearch.toLowerCase();
    return fullName.includes(term) || code.includes(term);
  });

  const toggleSelectAllEmployees = () => {
    if (selectedEmployeeIds.length === filteredEmployees.length) {
      setSelectedEmployeeIds([]);
    } else {
      setSelectedEmployeeIds(filteredEmployees.map((e) => e.id));
    }
  };

  const toggleEmployee = (empId: number) => {
    if (selectedEmployeeIds.includes(empId)) {
      setSelectedEmployeeIds(selectedEmployeeIds.filter((id) => id !== empId));
    } else {
      setSelectedEmployeeIds([...selectedEmployeeIds, empId]);
    }
  };

  const handlePreview = async () => {
    setError(null);
    if (!selectedPatternId) {
      setError('Por favor seleccione un patrón de turno.');
      return;
    }
    if (selectedEmployeeIds.length === 0) {
      setError('Por favor seleccione al menos un colaborador.');
      return;
    }

    try {
      const res = await previewPattern({
        pattern_id: Number(selectedPatternId),
        employee_ids: selectedEmployeeIds,
        start_offset_day: Number(startOffsetDay),
        start_date: startDate,
        end_date: endDate,
        override_existing: true,
        lock_version: currentLockVersion,
      });
      setPreviewResult(res);
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al calcular la previsualización del patrón.');
    }
  };

  const handleApply = async () => {
    setError(null);
    if (!selectedPatternId || selectedEmployeeIds.length === 0) return;

    try {
      await applyPattern({
        pattern_id: Number(selectedPatternId),
        employee_ids: selectedEmployeeIds,
        start_offset_day: Number(startOffsetDay),
        start_date: startDate,
        end_date: endDate,
        override_existing: true,
        lock_version: currentLockVersion,
      });
      onAppliedSuccess?.();
      onClose();
    } catch (err: any) {
      setError(err.response?.data?.message || 'Error al aplicar el patrón de turno.');
    }
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title="Aplicación Masiva de Patrones de Turno"
      size="xl"
    >
      <div className="space-y-6">
        {error && (
          <div className="p-3 bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-800 rounded-lg text-sm text-red-700 dark:text-red-300">
            {error}
          </div>
        )}

        {/* Sección 1: Selección de Patrón y Configuración */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-800/40 p-4 rounded-xl border border-gray-200 dark:border-gray-700/60">
          <div className="space-y-3">
            <Select
              label="Patrón de Turno a Aplicar"
              value={selectedPatternId ? String(selectedPatternId) : ''}
              onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
                setSelectedPatternId(e.target.value ? Number(e.target.value) : undefined);
                setPreviewResult(null);
              }}
              options={[
                { value: '', label: '-- Seleccione un Patrón --' },
                ...patterns.map((pat) => ({
                  value: String(pat.id),
                  label: `${pat.name} (${pat.code}) — Ciclo ${pat.cycle_length_days}d`,
                })),
              ]}
              required
            />

            {selectedPattern && (
              <div className="p-3 bg-white dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700 text-xs space-y-1">
                <div className="flex justify-between font-medium text-gray-900 dark:text-gray-100">
                  <span>Longitud del Ciclo:</span>
                  <span className="font-bold text-brand-600">{selectedPattern.cycle_length_days} días</span>
                </div>
                {selectedPattern.description && (
                  <p className="text-gray-500 dark:text-gray-400 italic">
                    "{selectedPattern.description}"
                  </p>
                )}
              </div>
            )}
          </div>

          <div className="space-y-3">
            <div>
              <label className="block text-xs font-semibold uppercase tracking-wider text-surface-700 mb-1">
                Día Inicial de Inicio de Secuencia
              </label>
              <input
                type="number"
                min="1"
                max={selectedPattern ? selectedPattern.cycle_length_days : 365}
                value={startOffsetDay}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setStartOffsetDay(parseInt(e.target.value) || 1);
                  setPreviewResult(null);
                }}
                className="w-full px-3.5 py-2 border border-surface-300 rounded-lg bg-white text-surface-900 text-sm"
              />
              <p className="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Indica qué día del ciclo (1 a {selectedPattern?.cycle_length_days || 'N'}) corresponde al primer día del rango.
              </p>
            </div>

            <div className="grid grid-cols-2 gap-2">
              <Input
                type="date"
                label="Desde"
                value={startDate}
                min={workPeriod.start_date}
                max={workPeriod.end_date}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setStartDate(e.target.value);
                  setPreviewResult(null);
                }}
              />
              <Input
                type="date"
                label="Hasta"
                value={endDate}
                min={startDate}
                max={workPeriod.end_date}
                onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                  setEndDate(e.target.value);
                  setPreviewResult(null);
                }}
              />
            </div>
          </div>
        </div>

        {/* Sección 2: Selección de Colaboradores */}
        <div className="space-y-3">
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
            <div className="flex items-center gap-2">
              <Users className="w-4 h-4 text-brand-600" />
              <h4 className="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Colaboradores Destinatarios ({selectedEmployeeIds.length} seleccionados)
              </h4>
            </div>
            <div className="flex items-center gap-2">
              <div className="w-48">
                <Input
                  placeholder="Buscar colaborador..."
                  leftIcon={<Search className="w-3.5 h-3.5" />}
                  value={empSearch}
                  onChange={(e: React.ChangeEvent<HTMLInputElement>) => setEmpSearch(e.target.value)}
                />
              </div>
              <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={toggleSelectAllEmployees}
                className="text-xs"
              >
                {selectedEmployeeIds.length === filteredEmployees.length ? 'Deseleccionar' : 'Todos'}
              </Button>
            </div>
          </div>

          <div className="max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-800 rounded-xl divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-900">
            {filteredEmployees.length === 0 ? (
              <div className="p-4 text-center text-xs text-gray-500 dark:text-gray-400">
                No hay colaboradores disponibles para el departamento del periodo.
              </div>
            ) : (
              filteredEmployees.map((emp) => {
                const isSelected = selectedEmployeeIds.includes(emp.id);
                return (
                  <label
                    key={emp.id}
                    className={`flex items-center justify-between p-2.5 px-3 hover:bg-gray-50 dark:hover:bg-gray-800/40 cursor-pointer transition-colors ${
                      isSelected ? 'bg-brand-50/50 dark:bg-brand-950/20' : ''
                    }`}
                  >
                    <div className="flex items-center gap-3">
                      <input
                        type="checkbox"
                        checked={isSelected}
                        onChange={() => toggleEmployee(emp.id)}
                        className="w-4 h-4 rounded text-brand-600 border-gray-300 focus:ring-brand-500"
                      />
                      <div>
                        <p className="text-xs font-semibold text-gray-900 dark:text-gray-100">
                          {emp.first_name} {emp.last_name}
                        </p>
                        <p className="text-[11px] text-gray-500 dark:text-gray-400">
                          {emp.employee_code} • {emp.position || 'Sin cargo'}
                        </p>
                      </div>
                    </div>
                    {isSelected && (
                      <span className="text-[11px] font-semibold text-brand-600 dark:text-brand-400">
                        Seleccionado
                      </span>
                    )}
                  </label>
                );
              })
            )}
          </div>
        </div>

        {/* Sección 3: Resultados de Previsualización (Dry-Run) */}
        {previewResult && (
          <div className="bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/40 rounded-xl p-4 space-y-3">
            <div className="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-300">
              <CheckCircle2 className="w-4 h-4 text-emerald-600" />
              <span>Simulación de Asignaciones Calculada</span>
            </div>

            <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
              <div className="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                <span className="block text-[11px] text-gray-500">Colaboradores</span>
                <span className="text-base font-bold text-gray-900 dark:text-gray-100">
                  {previewResult.summary.employees_count}
                </span>
              </div>
              <div className="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                <span className="block text-[11px] text-gray-500">Total Asignaciones</span>
                <span className="text-base font-bold text-brand-600">
                  {previewResult.summary.total_assignments}
                </span>
              </div>
              <div className="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                <span className="block text-[11px] text-gray-500">Horas Totales</span>
                <span className="text-base font-bold text-emerald-600">
                  {previewResult.summary.total_work_hours} hrs
                </span>
              </div>
              <div className="bg-white dark:bg-gray-800 p-2.5 rounded-lg border border-emerald-100 dark:border-emerald-800/30">
                <span className="block text-[11px] text-gray-500">Sobreescrituras</span>
                <span className="text-base font-bold text-amber-600">
                  {previewResult.summary.overwritten_count}
                </span>
              </div>
            </div>

            {previewResult.summary.overwritten_count > 0 && (
              <div className="flex items-center gap-2 text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/30 p-2 rounded-lg border border-amber-200 dark:border-amber-800/40">
                <AlertTriangle className="w-4 h-4 shrink-0" />
                <span>
                  Se sobreescribirán {previewResult.summary.overwritten_count} asignación(es) existente(s) en la versión actual.
                </span>
              </div>
            )}
          </div>
        )}

        {/* Acciones del Modal */}
        <div className="flex flex-col sm:flex-row items-center justify-between gap-3 pt-4 border-t border-gray-100 dark:border-gray-800">
          <Button type="button" variant="secondary" onClick={onClose} disabled={isApplying}>
            Cancelar
          </Button>

          <div className="flex items-center gap-2 w-full sm:w-auto">
            <Button
              type="button"
              variant="outline"
              onClick={handlePreview}
              isLoading={isPreviewing}
              disabled={!selectedPatternId || selectedEmployeeIds.length === 0 || isApplying}
              leftIcon={<RefreshCw className="w-3.5 h-3.5" />}
              className="text-xs w-full sm:w-auto"
            >
              Simular Previsualización
            </Button>

            <Button
              type="button"
              variant="primary"
              onClick={handleApply}
              isLoading={isApplying}
              disabled={!previewResult || isApplying}
              leftIcon={<Sparkles className="w-3.5 h-3.5" />}
              className="text-xs w-full sm:w-auto"
            >
              Confirmar y Aplicar
            </Button>
          </div>
        </div>
      </div>
    </Modal>
  );
};
