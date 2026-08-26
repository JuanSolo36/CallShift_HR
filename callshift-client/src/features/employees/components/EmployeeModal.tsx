import React, { useEffect, useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import { Modal } from '@/components/ui/Modal';
import { Input } from '@/components/forms/Input';
import { Textarea } from '@/components/forms/Textarea';
import { Select } from '@/components/forms/Select';
import { Button } from '@/components/ui/Button';
import { Tabs } from '@/components/navigation/Tabs';
import { useDepartments } from '@/features/organization/hooks/useDepartments';
import { usePositions } from '@/features/organization/hooks/usePositions';
import { useEmploymentTypes } from '@/features/organization/hooks/useEmploymentTypes';
import { useEmployees } from '../hooks/useEmployees';
import type {
  EmployeeItem,
  CreateEmployeePayload,
  UpdateEmployeePayload,
} from '@/types/employee.types';

const employeeSchema = z.object({
  employee_code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  document_type: z.enum(['CC', 'CE', 'TI', 'PASSPORT', 'OTHER', 'NIT']),
  document_number: z.string().min(3, 'El documento debe tener al menos 3 caracteres.').max(40, 'Máximo 40 caracteres.'),
  first_name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(60),
  middle_name: z.string().optional().nullable(),
  last_name: z.string().min(2, 'El apellido debe tener al menos 2 caracteres.').max(60),
  second_last_name: z.string().optional().nullable(),
  email: z.string().email('Ingrese un correo electrónico válido.').max(120),
  personal_email: z.string().email('Correo personal inválido.').optional().nullable().or(z.literal('')),
  phone: z.string().optional().nullable(),
  birth_date: z.string().optional().nullable(),
  hire_date: z.string().min(4, 'La fecha de ingreso es requerida.'),
  termination_date: z.string().optional().nullable(),
  department_id: z.coerce.number().min(1, 'Seleccione un departamento.'),
  position_id: z.coerce.number().min(1, 'Seleccione un cargo.'),
  employment_type_id: z.coerce.number().min(1, 'Seleccione un tipo de contrato.'),
  supervisor_id: z.coerce.number().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE', 'ON_LEAVE', 'TERMINATED']),
  notes: z.string().optional().nullable(),
});

type EmployeeFormData = z.infer<typeof employeeSchema>;

interface EmployeeModalProps {
  isOpen: boolean;
  onClose: () => void;
  employeeToEdit?: EmployeeItem | null;
  onSubmit: (payload: CreateEmployeePayload | UpdateEmployeePayload) => void;
  isLoading?: boolean;
}

export const EmployeeModal: React.FC<EmployeeModalProps> = ({
  isOpen,
  onClose,
  employeeToEdit,
  onSubmit,
  isLoading = false,
}) => {
  const isEditing = !!employeeToEdit;
  const [activeTab, setActiveTab] = useState<'personal' | 'job'>('personal');

  // Selectores compactos
  const { compactDepartments } = useDepartments();
  const { compactPositions } = usePositions();
  const { compactEmploymentTypes } = useEmploymentTypes();
  const { compactEmployees } = useEmployees();

  const {
    register,
    handleSubmit,
    reset,
    watch,
    formState: { errors },
  } = useForm<EmployeeFormData>({
    resolver: zodResolver(employeeSchema),
    defaultValues: {
      employee_code: '',
      document_type: 'CC',
      document_number: '',
      first_name: '',
      middle_name: '',
      last_name: '',
      second_last_name: '',
      email: '',
      personal_email: '',
      phone: '',
      birth_date: '',
      hire_date: new Date().toISOString().split('T')[0],
      termination_date: '',
      department_id: undefined,
      position_id: undefined,
      employment_type_id: undefined,
      supervisor_id: null,
      status: 'ACTIVE',
      notes: '',
    },
  });

  const selectedDepartmentId = watch('department_id');

  useEffect(() => {
    if (isOpen) {
      setActiveTab('personal');
      if (employeeToEdit) {
        reset({
          employee_code: employeeToEdit.employee_code,
          document_type: employeeToEdit.document_type,
          document_number: employeeToEdit.document_number,
          first_name: employeeToEdit.first_name,
          middle_name: employeeToEdit.middle_name || '',
          last_name: employeeToEdit.last_name,
          second_last_name: employeeToEdit.second_last_name || '',
          email: employeeToEdit.email,
          personal_email: employeeToEdit.personal_email || '',
          phone: employeeToEdit.phone || '',
          birth_date: employeeToEdit.birth_date || '',
          hire_date: employeeToEdit.hire_date || '',
          termination_date: employeeToEdit.termination_date || '',
          department_id: employeeToEdit.department_id,
          position_id: employeeToEdit.position_id,
          employment_type_id: employeeToEdit.employment_type_id,
          supervisor_id: employeeToEdit.supervisor_id || null,
          status: employeeToEdit.status,
          notes: employeeToEdit.notes || '',
        });
      } else {
        reset({
          employee_code: '',
          document_type: 'CC',
          document_number: '',
          first_name: '',
          middle_name: '',
          last_name: '',
          second_last_name: '',
          email: '',
          personal_email: '',
          phone: '',
          birth_date: '',
          hire_date: new Date().toISOString().split('T')[0],
          termination_date: '',
          department_id: undefined,
          position_id: undefined,
          employment_type_id: undefined,
          supervisor_id: null,
          status: 'ACTIVE',
          notes: '',
        });
      }
    }
  }, [isOpen, employeeToEdit, reset]);

  const onFormSubmit = (data: EmployeeFormData) => {
    onSubmit({
      ...data,
      middle_name: data.middle_name || null,
      second_last_name: data.second_last_name || null,
      personal_email: data.personal_email || null,
      phone: data.phone || null,
      birth_date: data.birth_date || null,
      termination_date: data.termination_date || null,
      supervisor_id: data.supervisor_id ? Number(data.supervisor_id) : null,
      notes: data.notes || null,
    });
  };

  const documentTypeOptions = [
    { value: 'CC', label: 'Cédula de Ciudadanía (CC)' },
    { value: 'CE', label: 'Cédula de Extranjería (CE)' },
    { value: 'TI', label: 'Tarjeta de Identidad (TI)' },
    { value: 'PASSPORT', label: 'Pasaporte' },
    { value: 'NIT', label: 'NIT / RUT' },
    { value: 'OTHER', label: 'Otro Documento' },
  ];

  const statusOptions = [
    { value: 'ACTIVE', label: 'Activo' },
    { value: 'INACTIVE', label: 'Inactivo' },
    { value: 'ON_LEAVE', label: 'En Licencia / Incapacidad' },
    { value: 'TERMINATED', label: 'Retirado / Liquidado' },
  ];

  // Filtrar cargos pertenecientes al departamento o generales
  const filteredPositions = compactPositions.filter(
    (p) => !p.department_id || p.department_id === Number(selectedDepartmentId)
  );

  return (
    <Modal
      isOpen={isOpen}
      onClose={onClose}
      title={isEditing ? `Expediente: ${employeeToEdit.full_name}` : 'Registrar Nuevo Empleado'}
      size="lg"
    >
      <form onSubmit={handleSubmit(onFormSubmit)} className="space-y-4 text-left">
        <Tabs
          tabs={[
            { id: 'personal', label: '1. Información Personal' },
            { id: 'job', label: '2. Vinculación Laboral y Cargo' },
          ]}
          activeTab={activeTab}
          onChange={(tabId) => setActiveTab(tabId as 'personal' | 'job')}
        />

        {activeTab === 'personal' && (
          <div className="space-y-4 pt-2">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Input
                label="Primer Nombre"
                required
                placeholder="Ej. Carlos"
                error={errors.first_name?.message}
                {...register('first_name')}
              />
              <Input
                label="Segundo Nombre"
                placeholder="Ej. Alberto"
                error={errors.middle_name?.message}
                {...register('middle_name')}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Input
                label="Primer Apellido"
                required
                placeholder="Ej. Mendoza"
                error={errors.last_name?.message}
                {...register('last_name')}
              />
              <Input
                label="Segundo Apellido"
                placeholder="Ej. Gómez"
                error={errors.second_last_name?.message}
                {...register('second_last_name')}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <Select
                label="Tipo de Documento"
                options={documentTypeOptions}
                error={errors.document_type?.message}
                {...register('document_type')}
              />
              <Input
                label="Número de Documento"
                required
                placeholder="Ej. 10203040"
                error={errors.document_number?.message}
                {...register('document_number')}
              />
              <Input
                label="Fecha de Nacimiento"
                type="date"
                error={errors.birth_date?.message}
                {...register('birth_date')}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Input
                label="Correo Corporativo (Trabajo)"
                type="email"
                required
                placeholder="carlos.mendoza@empresa.com"
                error={errors.email?.message}
                {...register('email')}
              />
              <Input
                label="Correo Personal (Opcional)"
                type="email"
                placeholder="carlos.personal@gmail.com"
                error={errors.personal_email?.message}
                {...register('personal_email')}
              />
            </div>

            <Input
              label="Teléfono / Celular de Contacto"
              placeholder="+57 300 123 4567"
              error={errors.phone?.message}
              {...register('phone')}
            />
          </div>
        )}

        {activeTab === 'job' && (
          <div className="space-y-4 pt-2">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Input
                label="Código Interno de Empleado"
                required
                placeholder="Ej. EMP-001"
                error={errors.employee_code?.message}
                {...register('employee_code')}
              />

              <Select
                label="Estado Laboral"
                options={statusOptions}
                error={errors.status?.message}
                {...register('status')}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Select
                label="Departamento / Área"
                required
                options={[
                  { value: '', label: 'Seleccionar Departamento...' },
                  ...compactDepartments.map((d) => ({ value: String(d.id), label: `${d.name} (${d.code})` })),
                ]}
                error={errors.department_id?.message}
                {...register('department_id')}
              />

              <Select
                label="Cargo / Posición"
                required
                options={[
                  { value: '', label: 'Seleccionar Cargo...' },
                  ...filteredPositions.map((p) => ({ value: String(p.id), label: `${p.name} (${p.code})` })),
                ]}
                error={errors.position_id?.message}
                {...register('position_id')}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Select
                label="Tipo de Contrato / Régimen"
                required
                options={[
                  { value: '', label: 'Seleccionar Tipo de Contrato...' },
                  ...compactEmploymentTypes.map((t) => ({ value: String(t.id), label: `${t.name} (${t.code})` })),
                ]}
                error={errors.employment_type_id?.message}
                {...register('employment_type_id')}
              />

              <Select
                label="Supervisor Directo (Opcional)"
                options={[
                  { value: '', label: 'Sin Supervisor Asignado' },
                  ...compactEmployees
                    .filter((e) => !employeeToEdit || e.id !== employeeToEdit.id)
                    .map((e) => ({
                      value: String(e.id),
                      label: `${e.first_name} ${e.last_name} (${e.employee_code})`,
                    })),
                ]}
                error={errors.supervisor_id?.message}
                {...register('supervisor_id')}
              />
            </div>

            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <Input
                label="Fecha de Contratación / Ingreso"
                type="date"
                required
                error={errors.hire_date?.message}
                {...register('hire_date')}
              />

              <Input
                label="Fecha de Retiro (Si aplica)"
                type="date"
                error={errors.termination_date?.message}
                {...register('termination_date')}
              />
            </div>

            <Textarea
              label="Notas o Antecedentes Contractuales"
              placeholder="Observaciones de expediente, condiciones particulares..."
              rows={2}
              error={errors.notes?.message}
              {...register('notes')}
            />
          </div>
        )}

        <div className="flex items-center justify-end gap-2 pt-4 border-t border-surface-100">
          <Button type="button" variant="secondary" size="sm" onClick={onClose}>
            Cancelar
          </Button>
          <Button type="submit" variant="primary" size="sm" isLoading={isLoading}>
            {isEditing ? 'Guardar Cambios' : 'Registrar Empleado'}
          </Button>
        </div>
      </form>
    </Modal>
  );
};
