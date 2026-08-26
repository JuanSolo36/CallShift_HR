import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Textarea } from '@/components/forms/Textarea';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import type { DepartmentItem, CreateDepartmentPayload, UpdateDepartmentPayload } from '@/types/organization.types';

const departmentSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  cost_center_code: z.string().optional().nullable(),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

type DepartmentFormData = z.infer<typeof departmentSchema>;

interface DepartmentModalProps {
  isOpen: boolean;
  onClose: () => void;
  departmentToEdit?: DepartmentItem | null;
  onSubmit: (payload: CreateDepartmentPayload | UpdateDepartmentPayload) => void;
  isLoading?: boolean;
}

export const DepartmentModal: React.FC<DepartmentModalProps> = ({
  isOpen,
  onClose,
  departmentToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const isEditing = !!departmentToEdit;

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<DepartmentFormData>({
    resolver: zodResolver(departmentSchema),
    defaultValues: {
      name: '',
      code: '',
      cost_center_code: '',
      description: '',
      status: 'ACTIVE',
    },
  });

  useEffect(() => {
    if (isOpen) {
      if (departmentToEdit) {
        reset({
          name: departmentToEdit.name,
          code: departmentToEdit.code,
          cost_center_code: departmentToEdit.cost_center_code || '',
          description: departmentToEdit.description || '',
          status: departmentToEdit.status,
        });
      } else {
        reset({
          name: '',
          code: '',
          cost_center_code: '',
          description: '',
          status: 'ACTIVE',
        });
      }
    }
  }, [isOpen, departmentToEdit, reset]);

  const onFormSubmit = (data: DepartmentFormData) => {
    onSubmit({
      ...data,
      cost_center_code: data.cost_center_code || null,
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
      title={isEditing ? `Editar Departamento: ${departmentToEdit.name}` : 'Nuevo Departamento'}
      size="md"
    >
      <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-4 text-left">
        <Input
          label="Nombre del Departamento"
          required
          placeholder="Ej. Operaciones y Logística"
          error={errors.name?.message}
          {...register('name')}
        />

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            label="Código de Identificación"
            required
            placeholder="Ej. OPS_LOG"
            error={errors.code?.message}
            {...register('code')}
          />

          <Input
            label="Centro de Costo"
            placeholder="Ej. CC-OPS-01"
            error={errors.cost_center_code?.message}
            {...register('cost_center_code')}
          />
        </div>

        <Select
          label="Estado"
          options={statusOptions}
          error={errors.status?.message}
          {...register('status')}
        />

        <Textarea
          label="Descripción o Propósito"
          placeholder="Detalle de funciones o alcance de esta área..."
          rows={3}
          error={errors.description?.message}
          {...register('description')}
        />

        <div className="flex items-center justify-end gap-2 pt-4 border-t border-surface-100">
          <Button type="button" variant="secondary" size="sm" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
            {isEditing ? 'Guardar Cambios' : 'Crear Departamento'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
