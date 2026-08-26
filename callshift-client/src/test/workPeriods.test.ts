import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

const workPeriodSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(100, 'Máximo 100 caracteres.'),
  period_type: z.enum(['WEEKLY', 'BIWEEKLY', 'MONTHLY', 'CUSTOM']),
  department_id: z.coerce.number().optional().nullable(),
  start_date: z.string().min(8, 'Fecha de inicio requerida.'),
  end_date: z.string().min(8, 'Fecha de fin requerida.'),
}).refine((data) => {
  return new Date(data.start_date) <= new Date(data.end_date);
}, {
  message: 'La fecha de fin debe ser igual o posterior a la fecha de inicio.',
  path: ['end_date'],
});

function calculateDurationDays(startDate: string, endDate: string): number {
  const start = new Date(startDate);
  const end = new Date(endDate);
  if (isNaN(start.getTime()) || isNaN(end.getTime()) || end < start) return 0;
  const diffTime = end.getTime() - start.getTime();
  return Math.round(diffTime / (1000 * 3600 * 24)) + 1;
}

export function runWorkPeriodModuleTests() {
  console.log('🧪 Starting FASE 12 — Work Periods & Planning Cycles Unit & RBAC Tests...\n');

  // 1. Validar schema de periodo con datos válidos
  console.log('1. Probando validación de formulario de periodo laboral (Zod)...');
  const validPeriod = {
    name: 'Semana 35 - Operaciones',
    period_type: 'WEEKLY',
    department_id: 10,
    start_date: '2026-08-24',
    end_date: '2026-08-30',
  };
  const parseResult = workPeriodSchema.safeParse(validPeriod);
  assert(parseResult.success === true, 'El schema debe aceptar un periodo laboral válido');

  // 2. Validar rechazo cuando start_date > end_date
  console.log('2. Probando rechazo cuando start_date > end_date...');
  const invalidDatePeriod = {
    ...validPeriod,
    start_date: '2026-08-31',
    end_date: '2026-08-24',
  };
  const parseInvalidResult = workPeriodSchema.safeParse(invalidDatePeriod);
  assert(parseInvalidResult.success === false, 'Debe rechazar un periodo con fecha de inicio posterior a fecha de fin');

  // 3. Validar cálculo inclusivo de duración en días
  console.log('3. Probando cálculo inclusivo de duración en días...');
  const daysWeekly = calculateDurationDays('2026-08-24', '2026-08-30');
  assert(daysWeekly === 7, '2026-08-24 a 2026-08-30 deben ser 7 días exactos');

  const daysSingle = calculateDurationDays('2026-08-24', '2026-08-24');
  assert(daysSingle === 1, '2026-08-24 a 2026-08-24 debe ser 1 día exacto');

  // 4. Validar permisos de UI en useAuthStore para periodos (HR_ADMIN)
  console.log('4. Probando permisos de UI para gestión de periodos (HR_ADMIN)...');
  useAuthStore.getState().setAuth(
    {
      id: 20,
      company_id: 1,
      username: 'hr.admin',
      email: 'hr.admin@callshift.com',
      status: 'ACTIVE',
      role: { id: 2, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['schedules:view', 'schedules:create', 'schedules:update', 'schedules:publish'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_hr_work_periods'
  );

  assert(useAuthStore.getState().hasPermission('schedules:view') === true, 'HR_ADMIN debe tener schedules:view');
  assert(useAuthStore.getState().hasPermission('schedules:create') === true, 'HR_ADMIN debe tener schedules:create');
  assert(useAuthStore.getState().hasPermission('schedules:update') === true, 'HR_ADMIN debe tener schedules:update');
  assert(useAuthStore.getState().hasPermission('schedules:publish') === true, 'HR_ADMIN debe tener schedules:publish');

  // 5. Validar permisos de UI para visualizador (solo lectura)
  console.log('5. Probando permisos de UI para rol visualizador (solo lectura)...');
  useAuthStore.getState().setAuth(
    {
      id: 30,
      company_id: 1,
      username: 'auditor.viewer',
      email: 'viewer@callshift.com',
      status: 'ACTIVE',
      role: { id: 6, code: 'VIEWER', name: 'Visualizador' },
      permissions: ['schedules:view'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_viewer_work_periods'
  );

  assert(useAuthStore.getState().hasPermission('schedules:view') === true, 'VIEWER debe tener schedules:view');
  assert(useAuthStore.getState().hasPermission('schedules:create') === false, 'VIEWER NO debe tener schedules:create');
  assert(useAuthStore.getState().hasPermission('schedules:update') === false, 'VIEWER NO debe tener schedules:update');
  assert(useAuthStore.getState().hasPermission('schedules:publish') === false, 'VIEWER NO debe tener schedules:publish');

  console.log('\n✅ Todos los tests del módulo de Periodos Laborales de FASE 12 pasaron exitosamente (100%)!\n');
}

runWorkPeriodModuleTests();
