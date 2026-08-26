import React, { useState } from 'react';
import { Modal } from '@/components/ui/Modal';
import { Select } from '@/components/forms/Select';
import { Input } from '@/components/forms/Input';
import { Button } from '@/components/ui/Button';
import { Alert } from '@/components/feedback/Alert';
import type { WorkPeriodItem, WorkPeriodStatus, ChangeWorkPeriodStatusPayload } from '@/types/workPeriod.types';

interface ChangeStatusModalProps {
  isOpen: boolean;
  onClose: () => void;
  period: WorkPeriodItem | null;
  onSubmit: (payload: ChangeWorkPeriodStatusPayload) => void;
  isLoading?: boolean;
}

export const ChangeStatusModal: React.FC<ChangeStatusModalProps> = ({
  isOpen,
  onClose,
  period,
  onSubmit,
  isLoading = false,
}) => {
  const [selectedStatus, setSelectedStatus] = useState<WorkPeriodStatus>('REVIEW');
  const [reason, setReason] = useState('');

  if (!period) return null;

  const validTransitions: Record<WorkPeriodStatus, { value: WorkPeriodStatus; label: string }[]> = {
    DRAFT: [
      { value: 'REVIEW', label: 'Pasar a Revisión (REVIEW)' },
      { value: 'PUBLISHED', label: 'Publicar Directamente (PUBLISHED)' },
      { value: 'CLOSED', label: 'Cerrar Periodo (CLOSED)' },
    ],
    GENERATED: [
      { value: 'REVIEW', label: 'Pasar a Revisión (REVIEW)' },
      { value: 'DRAFT', label: 'Regresar a Borrador (DRAFT)' },
      { value: 'PUBLISHED', label: 'Publicar (PUBLISHED)' },
      { value: 'CLOSED', label: 'Cerrar (CLOSED)' },
    ],
    REVIEW: [
      { value: 'PUBLISHED', label: 'Aprobar y Publicar (PUBLISHED)' },
      { value: 'DRAFT', label: 'Devolver a Borrador (DRAFT)' },
      { value: 'CLOSED', label: 'Cerrar Periodo (CLOSED)' },
    ],
    PUBLISHED: [
      { value: 'REVIEW', label: 'Reabrir para Revisión / Ajustes (REVIEW)' },
      { value: 'CLOSED', label: 'Cerrar / Archivar Periodo (CLOSED)' },
    ],
    CLOSED: [],
  };

  const availableOptions = validTransitions[period.status] || [];

  const handleFormSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    onSubmit({
      status: selectedStatus,
      reason: reason.trim() || undefined,
      lock_version: period.current_version?.lock_version,
    });
  };

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={`Cambiar Estado: ${period.name}`}
      size="sm"
    >
      <form onSubmit={handleFormSubmit} className="space-y-4 text-left">
        <div className="text-xs text-surface-600 bg-surface-50 p-2.5 rounded border border-surface-200">
          Estado actual: <span className="font-semibold text-surface-900">{period.status_label}</span>
        </div>

        {availableOptions.length === 0 ? (
          <Alert variant="warning" title="Estado Terminal">
            Este periodo se encuentra en estado CERRADO y no admite transiciones automáticas.
          </Alert>
        ) : (
          <>
            <Select
              label="Nuevo Estado"
              options={availableOptions}
              value={selectedStatus}
              onChange={(e) => setSelectedStatus(e.target.value as WorkPeriodStatus)}
            />

            <Input
              label="Motivo o Justificación (Opcional)"
              placeholder="Ej. Aprobación final de turnos para publicación..."
              value={reason}
              onChange={(e) => setReason(e.target.value)}
            />

            <div className="flex items-center justify-end gap-2 pt-3 border-t border-surface-100">
              <Button type="button" variant="secondary" size="sm" onClick={onClose}>
                Cancelar
              </Button>
              <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
                Confirmar Cambio
              </Button>
            </div>
          </>
        )}
      </form>
    </Modal>
  );
};
