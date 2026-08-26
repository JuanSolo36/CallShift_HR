import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

const departmentSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  cost_center_code: z.string().optional().nullable(),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

const positionSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  department_id: z.coerce.number().optional().nullable(),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

export function runOrganizationModuleTests() {
  console.log('🧪 Starting FASE 8 — Organizational Structure Unit & RBAC Tests...\n');

  // 1. Validar schema de departamento con datos válidos
  console.log('1. Probando validación de formulario de departamento (Zod)...');
  const validDept = {
    name: 'Operaciones y Logística',
    code: 'OPS_LOG',
    cost_center_code: 'CC-OPS-01',
    description: 'Área encargada de turnos operativos y logística en campo.',
    status: 'ACTIVE',
  };
  const parseDeptResult = departmentSchema.safeParse(validDept);
  assert(parseDeptResult.success === true, 'El schema debe aceptar un departamento válido');

  // 2. Validar rechazo por código de departamento demasiado corto
  console.log('2. Probando rechazo por código de departamento inválido...');
  const invalidDept = {
    name: 'Operaciones',
    code: 'O', // muy corto (< 2)
    status: 'ACTIVE',
  };
  const invalidDeptResult = departmentSchema.safeParse(invalidDept);
  assert(invalidDeptResult.success === false, 'Debe rechazar un código con menos de 2 caracteres');

  // 3. Validar schema de cargo con datos válidos
  console.log('3. Probando validación de formulario de cargo (Zod)...');
  const validPos = {
    name: 'Supervisor de Operaciones',
    code: 'OPS_SUP',
    department_id: 10,
    description: 'Supervisión en sitio y control de jornadas.',
    status: 'ACTIVE',
  };
  const parsePosResult = positionSchema.safeParse(validPos);
  assert(parsePosResult.success === true, 'El schema debe aceptar un cargo válido');

  // 4. Validar permisos de UI en useAuthStore para gestión organizacional
  console.log('4. Probando permisos de UI para gestión de estructura (HR_ADMIN)...');
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
    'token_hr_org'
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
    'token_viewer_org'
  );

  assert(useAuthStore.getState().hasPermission('organization:view') === true, 'VIEWER debe tener organization:view');
  assert(useAuthStore.getState().hasPermission('organization:manage') === false, 'VIEWER NO debe tener organization:manage');

  console.log('\n✅ Todos los tests del módulo de Estructura Organizacional de FASE 8 pasaron exitosamente (100%)!\n');
}

runOrganizationModuleTests();
