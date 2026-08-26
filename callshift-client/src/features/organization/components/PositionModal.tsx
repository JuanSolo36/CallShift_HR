import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Textarea } from '@/components/forms/Textarea';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import { useDepartments } from '../hooks/useDepartments';
import type { PositionItem, CreatePositionPayload, UpdatePositionPayload } from '@/types/organization.types';

const positionSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  department_id: z.coerce.number().optional().nullable(),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

type PositionFormData = z.infer<typeof positionSchema>;

interface PositionModalProps {
  isOpen: boolean;
  onClose: () => void;
  positionToEdit?: PositionItem | null;
  onSubmit: (payload: CreatePositionPayload | UpdatePositionPayload) => void;
  isLoading?: boolean;
}

export const PositionModal: React.FC<PositionModalProps> = ({
  isOpen,
  onClose,
  positionToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const isEditing = !!positionToEdit;
  const { compactDepartments } = useDepartments();

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<PositionFormData>({
    resolver: zodResolver(positionSchema),
    defaultValues: {
      name: '',
      code: '',
      department_id: null,
      description: '',
      status: 'ACTIVE',
    },
  });

  useEffect(() => {
    if (isOpen) {
      if (positionToEdit) {
        reset({
          name: positionToEdit.name,
          code: positionToEdit.code,
          department_id: positionToEdit.department_id || null,
          description: positionToEdit.description || '',
          status: positionToEdit.status,
        });
      } else {
        reset({
          name: '',
          code: '',
          department_id: null,
          description: '',
          status: 'ACTIVE',
        });
      }
    }
  }, [isOpen, positionToEdit, reset]);

  const onFormSubmit = (data: PositionFormData) => {
    onSubmit({
      ...data,
      department_id: data.department_id ? Number(data.department_id) : null,
      description: data.description || null,
    });
  };

  const departmentOptions = [
    { value: '', label: 'Sin Departamento (General)' },
    ...compactDepartments.map((d) => ({
      value: String(d.id),
      label: `${d.name} (${d.code})`,
    })),
  ];

  const statusOptions = [
    { value: 'ACTIVE', label: 'Activo' },
    { value: 'INACTIVE', label: 'Inactivo' },
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? `Editar Cargo: ${positionToEdit.name}` : 'Nuevo Cargo / Posición'}
      size="md"
    >
      <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-4 text-left">
        <Input
          label="Nombre del Cargo"
          required
          placeholder="Ej. Supervisor de Operaciones"
          error={errors.name?.message}
          {...register('name')}
        />

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            label="Código de Cargo"
            required
            placeholder="Ej. OPS_SUP"
            error={errors.code?.message}
            {...register('code')}
          />

          <Select
            label="Departamento"
            options={departmentOptions}
            error={errors.department_id?.message}
            {...register('department_id')}
          />
        </div>

        <Select
          label="Estado"
          options={statusOptions}
          error={errors.status?.message}
          {...register('status')}
        />

        <Textarea
          label="Descripción o Responsabilidades"
          placeholder="Resumen del rol y alcance de responsabilidades..."
          rows={3}
          error={errors.description?.message}
          {...register('description')}
        />

        <div className="flex items-center justify-end gap-2 pt-4 border-t border-surface-100">
          <Button type="button" variant="secondary" size="sm" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
            {isEditing ? 'Guardar Cambios' : 'Crear Cargo'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
