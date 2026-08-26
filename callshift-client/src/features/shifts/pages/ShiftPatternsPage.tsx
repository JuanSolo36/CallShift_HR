import React, { useState } from 'react';
import { useShiftPatterns } from '@/features/shifts/hooks/useShiftPatterns';
import { useDepartments } from '@/features/organization/hooks/useDepartments';
import { ShiftPattern } from '@/types/shiftPattern';
import { ShiftPatternModal } from '@/features/shifts/components/ShiftPatternModal';
import { Button } from '@/components/ui/Button';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { ConfirmDialog } from '@/components/feedback/ConfirmDialog';
import { EmptyState } from '@/components/feedback/EmptyState';
import { useAuthStore } from '@/stores/useAuthStore';
import {
  Sparkles,
  Plus,
  Search,
  Edit2,
  Trash2,
  Layers,
  Building2,
} from 'lucide-react';

export const ShiftPatternsPage: React.FC = () => {
  const { user } = useAuthStore();
  const { compactDepartments: departments } = useDepartments();

  const [searchTerm, setSearchTerm] = useState('');
  const [selectedDept, setSelectedDept] = useState<number | undefined>();
  const [selectedStatus, setSelectedStatus] = useState<string>('');

  const {
    patterns,
    isLoading,
    createPattern,
    updatePattern,
    deletePattern,
    isCreating,
    isUpdating,
  } = useShiftPatterns({
    search: searchTerm || undefined,
    department_id: selectedDept,
    status: selectedStatus || undefined,
  });

  const [isModalOpen, setIsModalOpen] = useState(false);
  const [editingPattern, setEditingPattern] = useState<ShiftPattern | null>(null);
  const [patternToDelete, setPatternToDelete] = useState<ShiftPattern | null>(null);

  const canManage =
    user?.role?.code === 'SUPER_ADMIN' ||
    user?.role?.code === 'HR_ADMIN' ||
    user?.role?.code === 'MANAGER' ||
    user?.permissions.includes('shifts:manage');

  const handleOpenCreate = () => {
    setEditingPattern(null);
    setIsModalOpen(true);
  };

  const handleOpenEdit = (pat: ShiftPattern) => {
    setEditingPattern(pat);
    setIsModalOpen(true);
  };

  const handleSave = async (data: Partial<ShiftPattern>) => {
    if (editingPattern) {
      await updatePattern({ id: editingPattern.id, data });
    } else {
      await createPattern(data);
    }
  };

  const handleDelete = async () => {
    if (patternToDelete) {
      await deletePattern(patternToDelete.id);
      setPatternToDelete(null);
    }
  };

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2.5">
            <Layers className="w-7 h-7 text-brand-600" />
            <span>Patrones de Turno y Ciclos</span>
          </h1>
          <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
            Diseñe secuencias rotativas (5x2, 6x1, 4x2) y aplíquelas masivamente sobre mallas de planificación.
          </p>
        </div>

        {canManage && (
          <Button onClick={handleOpenCreate} variant="primary" leftIcon={<Plus className="w-4 h-4" />}>
            Nuevo Patrón
          </Button>
        )}
      </div>

      {/* Filtros de Búsqueda */}
      <div className="bg-white dark:bg-gray-900 p-4 rounded-xl border border-gray-200 dark:border-gray-800 shadow-sm flex flex-col md:flex-row gap-3 items-center justify-between">
        <div className="w-full md:w-80">
          <Input
            placeholder="Buscar por nombre o código..."
            leftIcon={<Search className="w-4 h-4" />}
            value={searchTerm}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
          />
        </div>

        <div className="flex flex-wrap items-center gap-3 w-full md:w-auto">
          <div className="w-56">
            <Select
              value={selectedDept ? String(selectedDept) : ''}
              onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setSelectedDept(e.target.value ? Number(e.target.value) : undefined)}
              options={[
                { value: '', label: 'Todos los Departamentos' },
                ...departments.map((d) => ({ value: String(d.id), label: d.name })),
              ]}
            />
          </div>

          <div className="w-44">
            <Select
              value={selectedStatus}
              onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setSelectedStatus(e.target.value)}
              options={[
                { value: '', label: 'Todos los Estados' },
                { value: 'ACTIVE', label: 'Activo' },
                { value: 'INACTIVE', label: 'Inactivo' },
              ]}
            />
          </div>
        </div>
      </div>

      {/* Listado de Patrones */}
      {isLoading ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {[1, 2, 3].map((n) => (
            <div
              key={n}
              className="h-48 bg-gray-100 dark:bg-gray-800 rounded-xl animate-pulse"
            />
          ))}
        </div>
      ) : patterns.length === 0 ? (
        <EmptyState
          icon={<Sparkles className="w-6 h-6 stroke-[1.5]" />}
          title="No hay patrones de turno registrados"
          description="Cree su primer patrón de turno cíclico (ej. 5x2, 6x1) para automatizar la planificación horaria."
          actionText={canManage ? 'Crear Primer Patrón' : undefined}
          onAction={canManage ? handleOpenCreate : undefined}
        />
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
          {patterns.map((pat) => {
            const workCount = (pat.entries || []).filter((e) => e.day_type === 'WORK').length;
            const restCount = (pat.entries || []).filter((e) => e.day_type !== 'WORK').length;

            return (
              <div
                key={pat.id}
                className="bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 rounded-2xl p-5 shadow-sm hover:shadow-md transition-all flex flex-col justify-between"
              >
                <div className="space-y-4">
                  {/* Encabezado de la Card */}
                  <div className="flex items-start justify-between gap-3">
                    <div>
                      <span className="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-brand-50 text-brand-700 dark:bg-brand-950/40 dark:text-brand-300 mb-1">
                        {pat.code}
                      </span>
                      <h3 className="font-bold text-gray-900 dark:text-gray-100 text-base">
                        {pat.name}
                      </h3>
                    </div>
                    <span
                      className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold ${
                        pat.status === 'ACTIVE'
                          ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                          : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'
                      }`}
                    >
                      {pat.status === 'ACTIVE' ? 'Activo' : 'Inactivo'}
                    </span>
                  </div>

                  {/* Descripción / Departamento */}
                  <div className="space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <div className="flex items-center gap-1.5">
                      <Building2 className="w-3.5 h-3.5 text-gray-400" />
                      <span>{pat.department?.name || 'Toda la Empresa'}</span>
                    </div>
                    {pat.description && (
                      <p className="line-clamp-2 italic text-gray-600 dark:text-gray-300">
                        {pat.description}
                      </p>
                    )}
                  </div>

                  {/* Visualización de la Secuencia de Días */}
                  <div className="space-y-2 pt-2 border-t border-gray-100 dark:border-gray-800">
                    <div className="flex items-center justify-between text-xs text-gray-500">
                      <span>Secuencia ({pat.cycle_length_days} días)</span>
                      <span>
                        <strong className="text-gray-900 dark:text-gray-100">{workCount} Lab</strong> /{' '}
                        <strong className="text-gray-900 dark:text-gray-100">{restCount} Desc</strong>
                      </span>
                    </div>

                    <div className="flex flex-wrap gap-1">
                      {(pat.entries || []).map((entry) => {
                        const isWork = entry.day_type === 'WORK';
                        const color = isWork ? entry.shift_type?.color_hex || '#3B82F6' : '#9CA3AF';

                        return (
                          <div
                            key={entry.day_number}
                            title={`Día ${entry.day_number}: ${
                              isWork ? entry.shift_type?.name || 'Laboral' : entry.day_type
                            }`}
                            className="flex flex-col items-center justify-center w-7 h-8 rounded text-[10px] font-bold border transition-transform hover:scale-105"
                            style={{
                              backgroundColor: isWork ? `${color}20` : '#F3F4F6',
                              borderColor: color,
                              color: color,
                            }}
                          >
                            <span className="text-[8px] opacity-70">D{entry.day_number}</span>
                            <span>{isWork ? (entry.shift_type?.code || 'T').slice(0, 3) : 'OFF'}</span>
                          </div>
                        );
                      })}
                    </div>
                  </div>
                </div>

                {/* Acciones */}
                {canManage && (
                  <div className="flex items-center justify-end gap-2 pt-4 mt-4 border-t border-gray-100 dark:border-gray-800">
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => handleOpenEdit(pat)}
                      leftIcon={<Edit2 className="w-3.5 h-3.5" />}
                    >
                      Editar
                    </Button>
                    <Button
                      size="sm"
                      variant="ghost"
                      onClick={() => setPatternToDelete(pat)}
                      leftIcon={<Trash2 className="w-3.5 h-3.5 text-rose-500" />}
                      className="text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                    >
                      Eliminar
                    </Button>
                  </div>
                )}
              </div>
            );
          })}
        </div>
      )}

      {/* Modal de Creación / Edición */}
      <ShiftPatternModal
        isOpen={isModalOpen}
        onClose={() => setIsModalOpen(false)}
        onSave={handleSave}
        pattern={editingPattern}
        isLoading={isCreating || isUpdating}
      />

      {/* Confirmación de Eliminación */}
      <ConfirmDialog
        isOpen={!!patternToDelete}
        onClose={() => setPatternToDelete(null)}
        onConfirm={handleDelete}
        title="Eliminar Patrón de Turno"
        message={`¿Está seguro de que desea eliminar el patrón '${patternToDelete?.name}'? Esta acción no afectará asignaciones pasadas ya persistidas en horarios.`}
        confirmText="Eliminar Patrón"
        variant="danger"
      />
    </div>
  );
};
