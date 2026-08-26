import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

const shiftTypeSchema = z.object({
  name: z.string().min(2, 'El nombre debe tener al menos 2 caracteres.').max(80, 'Máximo 80 caracteres.'),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30, 'Máximo 30 caracteres.'),
  color_hex: z.string().regex(/^#([a-fA-F0-9]{6})$/, 'Color hexadecimal inválido (ej. #3B82F6).'),
  start_time: z.string().min(4, 'Hora de inicio requerida.'),
  end_time: z.string().min(4, 'Hora de fin requerida.'),
  break_duration_minutes: z.coerce.number().min(0, 'Mínimo 0 minutos.').max(360, 'Máximo 360 minutos.'),
  total_work_hours: z.coerce.number().optional().nullable(),
  crosses_midnight: z.boolean().optional(),
  description: z.string().optional().nullable(),
  status: z.enum(['ACTIVE', 'INACTIVE']),
});

// Helper de cálculo temporal idéntico a la UI
function computeShiftHours(startTime: string, endTime: string, breakMinutes: number = 60) {
  const [sh, sm] = startTime.split(':').map(Number);
  const [eh, em] = endTime.split(':').map(Number);

  const startM = sh * 60 + sm;
  const endM = eh * 60 + em;

  let crosses = false;
  let rawM = 0;

  if (endM < startM) {
    crosses = true;
    rawM = (1440 - startM) + endM;
  } else if (endM > startM) {
    crosses = false;
    rawM = endM - startM;
  } else {
    crosses = true;
    rawM = 1440;
  }

  const effectiveM = Math.max(0, rawM - breakMinutes);
  return {
    crossesMidnight: crosses,
    effectiveHours: Number((effectiveM / 60).toFixed(2)),
    rawHours: Number((rawM / 60).toFixed(2)),
  };
}

export function runShiftTypesTests() {
  console.log('🧪 Starting FASE 11 — Shift Types & Schedule Intervals Unit & RBAC Tests...\n');

  // 1. Validar schema de turno diurno con datos válidos
  console.log('1. Probando validación de formulario de turno diurno (Zod)...');
  const validDayShift = {
    name: 'Mañana Estándar (06:00 - 14:00)',
    code: 'M06_14',
    color_hex: '#3B82F6',
    start_time: '06:00',
    end_time: '14:00',
    break_duration_minutes: 60,
    total_work_hours: 7.00,
    crosses_midnight: false,
    status: 'ACTIVE',
  };
  const parseDayResult = shiftTypeSchema.safeParse(validDayShift);
  assert(parseDayResult.success === true, 'El schema debe aceptar un turno diurno válido');

  // 2. Validar cálculo y schema de turno nocturno con cruce de medianoche (22:00 -> 06:00)
  console.log('2. Probando cálculo y validación de turno nocturno (22:00 - 06:00)...');
  const nightCalc = computeShiftHours('22:00', '06:00', 60);
  assert(nightCalc.crossesMidnight === true, 'Debe identificar que 22:00 -> 06:00 cruza medianoche');
  assert(nightCalc.rawHours === 8.00, 'Debe calcular 8 horas brutas');
  assert(nightCalc.effectiveHours === 7.00, 'Debe calcular 7 horas efectivas descontando 60 min de descanso');

  const validNightShift = {
    name: 'Nocturno (22:00 - 06:00)',
    code: 'N22_06',
    color_hex: '#6366F1',
    start_time: '22:00',
    end_time: '06:00',
    break_duration_minutes: 60,
    total_work_hours: nightCalc.effectiveHours,
    crosses_midnight: nightCalc.crossesMidnight,
    status: 'ACTIVE',
  };
  const parseNightResult = shiftTypeSchema.safeParse(validNightShift);
  assert(parseNightResult.success === true, 'El schema debe aceptar un turno nocturno válido');

  // 3. Validar rechazo por color hexadecimal inválido
  console.log('3. Probando rechazo por código de color inválido...');
  const invalidColorShift = {
    ...validDayShift,
    color_hex: 'blue-invalid',
  };
  const parseColorResult = shiftTypeSchema.safeParse(invalidColorShift);
  assert(parseColorResult.success === false, 'Debe rechazar colores que no sean hexadecimales válidos');

  // 4. Validar permisos de UI en useAuthStore para gestión de turnos (HR_ADMIN)
  console.log('4. Probando permisos de UI para gestión de turnos (HR_ADMIN)...');
  useAuthStore.getState().setAuth(
    {
      id: 20,
      company_id: 1,
      username: 'hr.admin',
      email: 'hr.admin@callshift.com',
      status: 'ACTIVE',
      role: { id: 2, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['shifts:view', 'shifts:manage'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_hr_shifts'
  );

  assert(useAuthStore.getState().hasPermission('shifts:view') === true, 'HR_ADMIN debe tener shifts:view');
  assert(useAuthStore.getState().hasPermission('shifts:manage') === true, 'HR_ADMIN debe tener shifts:manage');

  // 5. Validar permisos de UI para rol visualizador (solo lectura de turnos)
  console.log('5. Probando permisos de UI para rol visualizador (solo lectura)...');
  useAuthStore.getState().setAuth(
    {
      id: 30,
      company_id: 1,
      username: 'auditor.viewer',
      email: 'viewer@callshift.com',
      status: 'ACTIVE',
      role: { id: 6, code: 'VIEWER', name: 'Visualizador' },
      permissions: ['shifts:view'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_viewer_shifts'
  );

  assert(useAuthStore.getState().hasPermission('shifts:view') === true, 'VIEWER debe tener shifts:view');
  assert(useAuthStore.getState().hasPermission('shifts:manage') === false, 'VIEWER NO debe tener shifts:manage');

  console.log('\n✅ Todos los tests del módulo de Tipos de Turno de FASE 11 pasaron exitosamente (100%)!\n');
}

runShiftTypesTests();
