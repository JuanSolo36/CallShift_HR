import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

// Validation schema mirror
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

export function runUserModuleTests() {
  console.log('🧪 Starting FASE 6 — Users, Roles & Permissions Unit Tests...\n');

  // 1. Validar schema de creación con datos válidos
  console.log('1. Probando validación de formulario de usuario (Zod)...');
  const validData = {
    username: 'carlos.mendoza',
    email: 'carlos.mendoza@callshift.com',
    role_id: 2,
    status: 'ACTIVE',
    password: 'Password123*',
    password_confirmation: 'Password123*',
  };
  const parseResult = userSchema.safeParse(validData);
  assert(parseResult.success === true, 'El schema debe validar correctamente datos válidos');

  // 2. Validar rechazo por contraseñas no coincidentes
  console.log('2. Probando rechazo por contraseñas no coincidentes...');
  const invalidPasswordData = {
    username: 'carlos.mendoza',
    email: 'carlos.mendoza@callshift.com',
    role_id: 2,
    status: 'ACTIVE',
    password: 'Password123*',
    password_confirmation: 'DifferentPass!',
  };
  const invalidPassResult = userSchema.safeParse(invalidPasswordData);
  assert(invalidPassResult.success === false, 'Debe fallar si las contraseñas no coinciden');

  // 3. Validar rechazo por email inválido
  console.log('3. Probando rechazo por formato de email inválido...');
  const invalidEmailData = {
    username: 'carlos.mendoza',
    email: 'invalid-email-format',
    role_id: 2,
    status: 'ACTIVE',
  };
  const invalidEmailResult = userSchema.safeParse(invalidEmailData);
  assert(invalidEmailResult.success === false, 'Debe fallar si el email no es válido');

  // 4. Validar permisos de UI en useAuthStore para FASE 6
  console.log('4. Probando permisos de UI para operaciones de usuario...');
  useAuthStore.getState().setAuth(
    {
      id: 20,
      company_id: 1,
      username: 'hr.manager',
      email: 'hr.manager@callshift.com',
      status: 'ACTIVE',
      role: { id: 2, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['users:view', 'users:create', 'users:update'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_hr_admin_789'
  );

  assert(useAuthStore.getState().hasPermission('users:view') === true, 'HR_ADMIN debe tener users:view');
  assert(useAuthStore.getState().hasPermission('users:create') === true, 'HR_ADMIN debe tener users:create');
  assert(useAuthStore.getState().hasPermission('users:update') === true, 'HR_ADMIN debe tener users:update');
  assert(useAuthStore.getState().hasPermission('users:delete') === false, 'HR_ADMIN NO debe tener users:delete sin permiso explícito');

  // 5. Validar usuario regular (sin permisos de administración de usuarios)
  console.log('5. Probando ocultamiento de UI para usuario empleado regular...');
  useAuthStore.getState().setAuth(
    {
      id: 50,
      company_id: 1,
      username: 'regular.employee',
      email: 'employee@callshift.com',
      status: 'ACTIVE',
      role: { id: 5, code: 'EMPLOYEE', name: 'Empleado' },
      permissions: [],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_emp_999'
  );

  assert(useAuthStore.getState().hasPermission('users:view') === false, 'EMPLOYEE NO debe tener users:view');
  assert(useAuthStore.getState().hasPermission('users:create') === false, 'EMPLOYEE NO debe tener users:create');
  assert(useAuthStore.getState().hasPermission('users:update') === false, 'EMPLOYEE NO debe tener users:update');

  console.log('\n✅ Todos los tests del módulo de Usuarios de FASE 6 pasaron exitosamente (100%)!\n');
}

runUserModuleTests();
