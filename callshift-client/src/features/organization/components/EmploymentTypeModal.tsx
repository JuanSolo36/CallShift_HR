import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Textarea } from '@/components/forms/Textarea';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import type {
  EmploymentTypeItem,
  CreateEmploymentTypePayload,
  UpdateEmploymentTypePayload,
} from '@/types/employmentType.types';

const employmentTypeSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(60, 'Máximo 60 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  default_weekly_hours: z.coerce
    .number()
    .min(1.0, 'La jornada mínima es de 1.0 hora.')
    .max(60.0, 'La jornada no puede exceder las 60.0 horas semanales.'),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

type EmploymentTypeFormData = z.infer<typeof employmentTypeSchema>;

interface EmploymentTypeModalProps {
  isOpen: boolean;
  onClose: () => void;
  typeToEdit?: EmploymentTypeItem | null;
  onSubmit: (payload: CreateEmploymentTypePayload | UpdateEmploymentTypePayload) => void;
  isLoading?: boolean;
}

export const EmploymentTypeModal: React.FC<EmploymentTypeModalProps> = ({
  isOpen,
  onClose,
  typeToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const isEditing = !!typeToEdit;

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<EmploymentTypeFormData>({
    resolver: zodResolver(employmentTypeSchema),
    defaultValues: {
      name: '',
      code: '',
      default_weekly_hours: 40.0,
      description: '',
      status: 'ACTIVE',
    },
  });

  useEffect(() => {
    if (isOpen) {
      if (typeToEdit) {
        reset({
          name: typeToEdit.name,
          code: typeToEdit.code,
          default_weekly_hours: typeToEdit.default_weekly_hours,
          description: typeToEdit.description || '',
          status: typeToEdit.status,
        });
      } else {
        reset({
          name: '',
          code: '',
          default_weekly_hours: 40.0,
          description: '',
          status: 'ACTIVE',
        });
      }
    }
  }, [isOpen, typeToEdit, reset]);

  const onFormSubmit = (data: EmploymentTypeFormData) => {
    onSubmit({
      ...data,
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
      title={isEditing ? `Editar Tipo de Contrato: ${typeToEdit.name}` : 'Nuevo Tipo de Contrato / Jornada'}
      size="md"
    >
      <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-4 text-left">
        <Input
          label="Nombre del Tipo de Contrato"
          required
          placeholder="Ej. Tiempo Completo Ordinario"
          error={errors.name?.message}
          {...register('name')}
        />

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          <Input
            label="Código Identificador"
            required
            placeholder="Ej. FULL_TIME_48"
            error={errors.code?.message}
            {...register('code')}
          />

          <Input
            label="Horas Base Semanales"
            type="number"
            step="0.5"
            required
            placeholder="48.0"
            helperText="Base contractual semanal para el cálculo de turnos."
            error={errors.default_weekly_hours?.message}
            {...register('default_weekly_hours')}
          />
        </div>

        <Select
          label="Estado"
          options={statusOptions}
          error={errors.status?.message}
          {...register('status')}
        />

        <Textarea
          label="Descripción o Términos de Vinculación"
          placeholder="Alcance o especificaciones del régimen laboral..."
          rows={3}
          error={errors.description?.message}
          {...register('description')}
        />

        <div className="flex items-center justify-end gap-2 pt-4 border-t border-surface-100">
          <Button type="button" variant="secondary" size="sm" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
            {isEditing ? 'Guardar Cambios' : 'Crear Tipo de Contrato'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
