import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

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

export function runEmployeeModuleTests() {
  console.log('🧪 Starting FASE 10 — Employees & Workforce Records Unit & RBAC Tests...\n');

  // 1. Validar schema de empleado con datos válidos
  console.log('1. Probando validación de formulario de empleado (Zod)...');
  const validEmployee = {
    employee_code: 'EMP-001',
    document_type: 'CC',
    document_number: '1020304050',
    first_name: 'Carlos',
    middle_name: 'Alberto',
    last_name: 'Mendoza',
    second_last_name: 'Gómez',
    email: 'carlos.mendoza@callshift.com',
    personal_email: 'carlos.personal@gmail.com',
    phone: '+57 300 123 4567',
    birth_date: '1990-05-15',
    hire_date: '2025-01-01',
    termination_date: null,
    department_id: 10,
    position_id: 100,
    employment_type_id: 1000,
    supervisor_id: null,
    status: 'ACTIVE',
    notes: 'Contratado para el turno principal de operaciones.',
  };
  const parseResult = employeeSchema.safeParse(validEmployee);
  assert(parseResult.success === true, 'El schema debe aceptar un empleado válido');

  // 2. Validar rechazo por código de empleado demasiado corto
  console.log('2. Probando rechazo por código de empleado inválido...');
  const invalidCodeEmployee = {
    ...validEmployee,
    employee_code: 'E', // muy corto (< 2)
  };
  const parseInvalidCode = employeeSchema.safeParse(invalidCodeEmployee);
  assert(parseInvalidCode.success === false, 'Debe rechazar un código con menos de 2 caracteres');

  // 3. Validar rechazo por email inválido
  console.log('3. Probando rechazo por formato de correo inválido...');
  const invalidEmailEmployee = {
    ...validEmployee,
    email: 'correo-invalido-sin-arroba',
  };
  const parseInvalidEmail = employeeSchema.safeParse(invalidEmailEmployee);
  assert(parseInvalidEmail.success === false, 'Debe rechazar correos con formato inválido');

  // 4. Validar permisos de UI en useAuthStore para gestión de empleados (HR_ADMIN)
  console.log('4. Probando permisos de UI para gestión de empleados (HR_ADMIN)...');
  useAuthStore.getState().setAuth(
    {
      id: 20,
      company_id: 1,
      username: 'hr.admin',
      email: 'hr.admin@callshift.com',
      status: 'ACTIVE',
      role: { id: 2, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['employees:view', 'employees:create', 'employees:update', 'employees:delete'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_hr_employees'
  );

  assert(useAuthStore.getState().hasPermission('employees:view') === true, 'HR_ADMIN debe tener employees:view');
  assert(useAuthStore.getState().hasPermission('employees:create') === true, 'HR_ADMIN debe tener employees:create');
  assert(useAuthStore.getState().hasPermission('employees:update') === true, 'HR_ADMIN debe tener employees:update');
  assert(useAuthStore.getState().hasPermission('employees:delete') === true, 'HR_ADMIN debe tener employees:delete');

  // 5. Validar permisos de UI para rol visualizador / viewer (solo lectura de empleados)
  console.log('5. Probando permisos de UI para rol visualizador (solo lectura)...');
  useAuthStore.getState().setAuth(
    {
      id: 30,
      company_id: 1,
      username: 'auditor.viewer',
      email: 'viewer@callshift.com',
      status: 'ACTIVE',
      role: { id: 6, code: 'VIEWER', name: 'Visualizador' },
      permissions: ['employees:view'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_viewer_employees'
  );

  assert(useAuthStore.getState().hasPermission('employees:view') === true, 'VIEWER debe tener employees:view');
  assert(useAuthStore.getState().hasPermission('employees:create') === false, 'VIEWER NO debe tener employees:create');
  assert(useAuthStore.getState().hasPermission('employees:update') === false, 'VIEWER NO debe tener employees:update');
  assert(useAuthStore.getState().hasPermission('employees:delete') === false, 'VIEWER NO debe tener employees:delete');

  console.log('\n✅ Todos los tests del módulo de Empleados de FASE 10 pasaron exitosamente (100%)!\n');
}

runEmployeeModuleTests();
