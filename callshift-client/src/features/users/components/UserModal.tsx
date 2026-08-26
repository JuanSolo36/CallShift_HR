import React, { useEffect } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import { useRoles } from '../hooks/useRoles';
import type { UserItem, CreateUserPayload, UpdateUserPayload } from '@/types/user.types';

const userSchema = z
  .object({
    username: z.string().min(3, 'El usuario debe tener al menos 3 caracteres.'),
    email: z.string().email('Debe ingresar un correo electrónico válido.'),
    role_id: z.coerce.number().min(1, 'Seleccione un rol.'),
    status: z.enum(['ACTIVE', 'INACTIVE', 'SUSPENDED']),
    password: z.string().optional(),
    password_confirmation: z.string().optional(),
  })
  .refine(
    (data) => {
      // Si la contraseña fue ingresada, debe coincidir con la confirmación
      if (data.password && data.password.length > 0) {
        return data.password === data.password_confirmation;
      }
      return true;
    },
    {
      message: 'Las contraseñas no coinciden.',
      path: ['password_confirmation'],
    }
  );

type UserFormValues = z.infer<typeof userSchema>;

export interface UserModalProps {
  isOpen: boolean;
  onClose: () => void;
  userToEdit?: UserItem | null;
  onSubmit: (payload: CreateUserPayload | UpdateUserPayload) => void;
  isLoading?: boolean;
}

export const UserModal: React.FC<UserModalProps> = ({
  isOpen,
  onClose,
  userToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const { roles } = useRoles();
  const isEditing = !!userToEdit;

  const {
    register,
    handleSubmit,
    reset,
    formState: { errors },
  } = useForm<UserFormValues>({
    resolver: zodResolver(userSchema),
    defaultValues: {
      username: '',
      email: '',
      role_id: 2,
      status: 'ACTIVE',
      password: '',
      password_confirmation: '',
    },
  });

  useEffect(() => {
    if (userToEdit) {
      reset({
        username: userToEdit.username,
        email: userToEdit.email,
        role_id: userToEdit.role?.id || 2,
        status: userToEdit.status,
        password: '',
        password_confirmation: '',
      });
    } else {
      reset({
        username: '',
        email: '',
        role_id: 2,
        status: 'ACTIVE',
        password: '',
        password_confirmation: '',
      });
    }
  }, [userToEdit, reset, isOpen]);

  const handleFormSubmit = (data: UserFormValues) => {
    if (isEditing) {
      const payload: UpdateUserPayload = {
        username: data.username,
        email: data.email,
        role_id: Number(data.role_id),
        status: data.status,
      };
      if (data.password && data.password.length > 0) {
        payload.password = data.password;
        payload.password_confirmation = data.password_confirmation;
      }
      onSubmit(payload);
    } else {
      const payload: CreateUserPayload = {
        username: data.username,
        email: data.email,
        password: data.password || 'TemporaryPass123*',
        password_confirmation: data.password_confirmation || data.password || 'TemporaryPass123*',
        role_id: Number(data.role_id),
        status: data.status,
      };
      onSubmit(payload);
    }
  };

  const roleOptions = roles.map((r) => ({
    value: r.id,
    label: `${r.name} (${r.code})`,
  }));

  const statusOptions = [
    { value: 'ACTIVE', label: 'Activo' },
    { value: 'INACTIVE', label: 'Inactivo' },
    { value: 'SUSPENDED', label: 'Suspendido' },
  ];

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      size="lg"
      title={isEditing ? `Editar Usuario: ${userToEdit.username}` : 'Registrar Nuevo Usuario'}
      description={
        isEditing
          ? 'Modifique los datos de acceso, asignación de rol o estado del usuario.'
          : 'Complete los datos obligatorios para crear una nueva cuenta de acceso.'
      }
      footer={
        <>
          <Button variant="secondary" size="sm" onClick={onClose} disabled={isLoading}>
            Cancelar
          </Button>
          <Button
            variant="primary"
            size="sm"
            onClick={handleSubmit(handleFormSubmit)}
            isLoading={isLoading}
          >
            {isEditing ? 'Guardar Cambios' : 'Crear Usuario'}
          </Button>
        </>
      }
    >
      <form onSubmit={handleSubmit(handleFormSubmit)} className="space-y-4 text-left">
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Input
            label="Nombre de Usuario"
            placeholder="ej. juan.perez"
            error={errors.username?.message}
            {...register('username')}
          />

          <Input
            label="Correo Electrónico"
            type="email"
            placeholder="ej. juan.perez@callshift.com"
            error={errors.email?.message}
            {...register('email')}
          />
        </div>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <Select
            label="Rol de Acceso"
            options={roleOptions}
            error={errors.role_id?.message}
            {...register('role_id')}
          />

          <Select
            label="Estado Inicial"
            options={statusOptions}
            error={errors.status?.message}
            {...register('status')}
          />
        </div>

        <div className="pt-2 border-t border-surface-100">
          <div className="text-xs font-semibold text-surface-900 mb-3">
            {isEditing ? 'Cambio de Contraseña (Opcional)' : 'Contraseña Inicial'}
          </div>

          <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <Input
              label={isEditing ? 'Nueva Contraseña' : 'Contraseña'}
              type="password"
              placeholder="••••••••••••"
              helperText={isEditing ? 'Deje en blanco para conservar la actual.' : 'Mínimo 8 caracteres.'}
              error={errors.password?.message}
              {...register('password')}
            />

            <Input
              label="Confirmar Contraseña"
              type="password"
              placeholder="••••••••••••"
              error={errors.password_confirmation?.message}
              {...register('password_confirmation')}
            />
          </div>
        </div>
      </form>
    </Modal>
  );
};
