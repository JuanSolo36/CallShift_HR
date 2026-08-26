import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

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

export function runEmploymentTypesTests() {
  console.log('🧪 Starting FASE 9 — Employment & Contract Types Unit & RBAC Tests...\n');

  // 1. Validar schema con datos válidos (Tiempo Completo 48 hrs)
  console.log('1. Probando validación de formulario de tipo de contrato (Zod)...');
  const validPayload = {
    name: 'Tiempo Completo Ordinario',
    code: 'FULL_TIME_48',
    default_weekly_hours: 48.0,
    description: 'Jornada ordinaria legal con descansos compensatorios.',
    status: 'ACTIVE',
  };
  const parseValid = employmentTypeSchema.safeParse(validPayload);
  assert(parseValid.success === true, 'El schema debe aceptar un tipo de contrato válido');

  // 2. Validar rechazo por código inválido (demasiado corto)
  console.log('2. Probando rechazo por código demasiado corto...');
  const invalidCodePayload = {
    name: 'Tiempo Parcial',
    code: 'P',
    default_weekly_hours: 24.0,
    status: 'ACTIVE',
  };
  const parseInvalidCode = employmentTypeSchema.safeParse(invalidCodePayload);
  assert(parseInvalidCode.success === false, 'Debe rechazar un código menor a 2 caracteres');

  // 3. Validar rechazo por horas fuera de rango (< 1.0 o > 60.0)
  console.log('3. Probando rechazo por horas base fuera de rango...');
  const tooLowHours = {
    name: 'Mini Jornada',
    code: 'MINI_0',
    default_weekly_hours: 0.5,
    status: 'ACTIVE',
  };
  const tooHighHours = {
    name: 'Sobrecarga Ilegal',
    code: 'OVER_80',
    default_weekly_hours: 80.0,
    status: 'ACTIVE',
  };
  assert(employmentTypeSchema.safeParse(tooLowHours).success === false, 'Debe rechazar jornada < 1.0 hora');
  assert(employmentTypeSchema.safeParse(tooHighHours).success === false, 'Debe rechazar jornada > 60.0 horas');

  // 4. Validar permisos de UI en useAuthStore para gestión de contratos (HR_ADMIN)
  console.log('4. Probando permisos de UI para gestión de tipos de contrato (HR_ADMIN)...');
  useAuthStore.getState().setAuth(
    {
      id: 20,
      company_id: 1,
      username: 'hr.admin',
      email: 'hr.admin@callshift.com',
      status: 'ACTIVE',
      role: { id: 2, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['organization:view', 'organization:manage'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_hr_contracts'
  );

  assert(useAuthStore.getState().hasPermission('organization:view') === true, 'HR_ADMIN debe tener organization:view');
  assert(useAuthStore.getState().hasPermission('organization:manage') === true, 'HR_ADMIN debe tener organization:manage');

  // 5. Validar permisos de UI para rol visualizador / viewer (solo lectura)
  console.log('5. Probando permisos de UI para usuario visualizador (solo lectura)...');
  useAuthStore.getState().setAuth(
    {
      id: 30,
      company_id: 1,
      username: 'auditor.viewer',
      email: 'viewer@callshift.com',
      status: 'ACTIVE',
      role: { id: 6, code: 'VIEWER', name: 'Visualizador' },
      permissions: ['organization:view'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_viewer_contracts'
  );

  assert(useAuthStore.getState().hasPermission('organization:view') === true, 'VIEWER debe tener organization:view');
  assert(useAuthStore.getState().hasPermission('organization:manage') === false, 'VIEWER NO debe tener organization:manage');

  console.log('\n✅ Todos los tests del módulo de Tipos de Contrato de FASE 9 pasaron exitosamente (100%)!\n');
}

runEmploymentTypesTests();
