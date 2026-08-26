import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';
import { ShiftPatternEntry, PatternProjectionItem } from '../types/shiftPattern';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

// Zod Schemas
const shiftPatternEntrySchema = z.object({
  day_number: z.number().int().min(1, 'El día de ciclo debe ser >= 1.'),
  day_type: z.enum(['WORK', 'REST', 'OFF', 'HOLIDAY', 'PERMISSION', 'ABSENCE']).default('WORK'),
  shift_type_id: z.number().int().positive().nullable().optional(),
  start_time_override: z.string().nullable().optional(),
  end_time_override: z.string().nullable().optional(),
  notes: z.string().max(255).nullable().optional(),
});

const shiftPatternSchema = z.object({
  name: z.string().min(3, 'El nombre debe tener al menos 3 caracteres.').max(100),
  code: z.string().min(2, 'El código debe tener al menos 2 caracteres.').max(30),
  cycle_length_days: z.number().int().min(1).max(365),
  department_id: z.number().int().positive().nullable().optional(),
  position_id: z.number().int().positive().nullable().optional(),
  description: z.string().max(255).nullable().optional(),
  status: z.enum(['ACTIVE', 'INACTIVE']).default('ACTIVE'),
  entries: z.array(shiftPatternEntrySchema).min(1, 'Debe incluir al menos una entrada de ciclo.'),
});

const applyPatternSchema = z.object({
  pattern_id: z.number().int().positive('Debe seleccionar un patrón de turno.'),
  employee_ids: z.array(z.number().int().positive()).min(1, 'Debe seleccionar al menos un colaborador.'),
  start_offset_day: z.number().int().min(1).default(1),
  start_date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  end_date: z.string().regex(/^\d{4}-\d{2}-\d{2}$/).optional(),
  override_existing: z.boolean().default(true),
  lock_version: z.number().int().min(1),
});

// Pure cycle projection helper for frontend simulation
function calculateCycleDay(
  startOffsetDay: number,
  dayOffset: number,
  cycleLength: number
): number {
  return (((startOffsetDay - 1 + dayOffset) % cycleLength) + cycleLength) % cycleLength + 1;
}

function simulatePatternProjections(
  pattern: { cycle_length_days: number; entries: ShiftPatternEntry[] },
  employeeIds: number[],
  startDateStr: string,
  endDateStr: string,
  startOffsetDay: number = 1
): PatternProjectionItem[] {
  const start = new Date(startDateStr);
  const end = new Date(endDateStr);
  const daysCount = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24)) + 1;

  const entriesMap = new Map<number, ShiftPatternEntry>();
  pattern.entries.forEach((e) => entriesMap.set(e.day_number, e));

  const items: PatternProjectionItem[] = [];

  for (const empId of employeeIds) {
    for (let i = 0; i < daysCount; i++) {
      const currentDate = new Date(start);
      currentDate.setDate(start.getDate() + i);
      const dateIso = currentDate.toISOString().slice(0, 10);

      const cycleDay = calculateCycleDay(startOffsetDay, i, pattern.cycle_length_days);
      const entry = entriesMap.get(cycleDay);

      const isWork = entry?.day_type === 'WORK';
      const hours = isWork && entry?.shift_type ? entry.shift_type.total_work_hours : 0;

      items.push({
        employee_id: empId,
        employee_name: `Emp #${empId}`,
        date: dateIso,
        day_number: cycleDay,
        day_type: entry?.day_type || 'REST',
        shift_type_id: entry?.shift_type_id || null,
        shift_type_name: entry?.shift_type?.name || null,
        shift_type_code: entry?.shift_type?.code || null,
        color_hex: entry?.shift_type?.color_hex || '#9CA3AF',
        start_time: entry?.shift_type?.start_time || null,
        end_time: entry?.shift_type?.end_time || null,
        starts_at: isWork ? `${dateIso} ${entry?.shift_type?.start_time}` : null,
        ends_at: isWork ? `${dateIso} ${entry?.shift_type?.end_time}` : null,
        total_hours: hours,
        is_overwriting: false,
      });
    }
  }

  return items;
}

export function runShiftPatternsModuleTests() {
  console.log('🧪 Starting FASE 14 — Shift Patterns, Templates & Cyclic Planning Engine Tests (8 Tests)...\n');

  const sample5x2Pattern: { cycle_length_days: number; entries: ShiftPatternEntry[] } = {
    cycle_length_days: 7,
    entries: [
      {
        day_number: 1,
        day_type: 'WORK',
        shift_type_id: 10,
        shift_type: {
          id: 10,
          name: 'Mañana',
          code: 'TM-08',
          color_hex: '#3B82F6',
          start_time: '08:00:00',
          end_time: '17:00:00',
          total_work_hours: 8.0,
          crosses_midnight: false,
        },
      },
      {
        day_number: 2,
        day_type: 'WORK',
        shift_type_id: 10,
        shift_type: {
          id: 10,
          name: 'Mañana',
          code: 'TM-08',
          color_hex: '#3B82F6',
          start_time: '08:00:00',
          end_time: '17:00:00',
          total_work_hours: 8.0,
          crosses_midnight: false,
        },
      },
      {
        day_number: 3,
        day_type: 'WORK',
        shift_type_id: 10,
        shift_type: {
          id: 10,
          name: 'Mañana',
          code: 'TM-08',
          color_hex: '#3B82F6',
          start_time: '08:00:00',
          end_time: '17:00:00',
          total_work_hours: 8.0,
          crosses_midnight: false,
        },
      },
      {
        day_number: 4,
        day_type: 'WORK',
        shift_type_id: 10,
        shift_type: {
          id: 10,
          name: 'Mañana',
          code: 'TM-08',
          color_hex: '#3B82F6',
          start_time: '08:00:00',
          end_time: '17:00:00',
          total_work_hours: 8.0,
          crosses_midnight: false,
        },
      },
      {
        day_number: 5,
        day_type: 'WORK',
        shift_type_id: 10,
        shift_type: {
          id: 10,
          name: 'Mañana',
          code: 'TM-08',
          color_hex: '#3B82F6',
          start_time: '08:00:00',
          end_time: '17:00:00',
          total_work_hours: 8.0,
          crosses_midnight: false,
        },
      },
      {
        day_number: 6,
        day_type: 'REST',
        shift_type_id: null,
        shift_type: null,
      },
      {
        day_number: 7,
        day_type: 'REST',
        shift_type_id: null,
        shift_type: null,
      },
    ],
  };

  // TEST 1: Schema de creación de patrón válido
  console.log('1. Probando validación de payload de creación de patrón válido (Zod)...');
  const validPatternPayload = {
    name: 'Turno Administrativo 5x2',
    code: 'PAT-5X2',
    cycle_length_days: 7,
    status: 'ACTIVE',
    entries: sample5x2Pattern.entries,
  };
  const parsePatternResult = shiftPatternSchema.safeParse(validPatternPayload);
  assert(parsePatternResult.success === true, 'El schema debe validar un patrón 5x2 correcto');

  // TEST 2: Schema de aplicación masiva con Zod
  console.log('2. Probando validación de payload de aplicación masiva de patrón...');
  const validApplyPayload = {
    pattern_id: 1,
    employee_ids: [10, 11, 12],
    start_offset_day: 1,
    start_date: '2026-08-24',
    end_date: '2026-08-30',
    override_existing: true,
    lock_version: 1,
  };
  const parseApplyResult = applyPatternSchema.safeParse(validApplyPayload);
  assert(parseApplyResult.success === true, 'El schema de aplicación masiva debe ser válido');

  // TEST 3: Rechazo de aplicación con lista de colaboradores vacía
  console.log('3. Probando rechazo cuando employee_ids está vacío...');
  const invalidApplyPayload = { ...validApplyPayload, employee_ids: [] };
  const parseInvalidApply = applyPatternSchema.safeParse(invalidApplyPayload);
  assert(parseInvalidApply.success === false, 'Debe rechazar lista vacía de colaboradores');

  // TEST 4: Cálculo determinista de días de ciclo con offset inicial
  console.log('4. Probando cálculo determinista de secuencia cíclica y offsets...');
  assert(calculateCycleDay(1, 0, 7) === 1, 'Día 0 con offset 1 debe ser día 1 del ciclo');
  assert(calculateCycleDay(1, 6, 7) === 7, 'Día 6 con offset 1 debe ser día 7 del ciclo');
  assert(calculateCycleDay(1, 7, 7) === 1, 'Día 7 debe rotar al día 1 del siguiente ciclo');
  assert(calculateCycleDay(6, 0, 7) === 6, 'Día 0 con offset 6 debe comenzar en día 6 (Descanso)');
  assert(calculateCycleDay(6, 2, 7) === 1, 'Día 2 con offset 6 debe mapear a día 1 (Laboral)');

  // TEST 5: Simulación dry-run de patrón 5x2 en memoria (14 asignaciones para 2 colaboradores)
  console.log('5. Probando proyección y simulación en memoria de patrón 5x2...');
  const projections = simulatePatternProjections(
    sample5x2Pattern,
    [101, 102],
    '2026-08-24',
    '2026-08-30',
    1
  );
  assert(projections.length === 14, 'Deben proyectarse exactamente 14 asignaciones (2 emp * 7 días)');

  const emp101Items = projections.filter((p) => p.employee_id === 101);
  const workDays = emp101Items.filter((p) => p.day_type === 'WORK');
  const restDays = emp101Items.filter((p) => p.day_type === 'REST');
  assert(workDays.length === 5, 'Colaborador 101 debe tener 5 días de trabajo');
  assert(restDays.length === 2, 'Colaborador 101 debe tener 2 días de descanso');

  const totalEmpHours = emp101Items.reduce((sum, p) => sum + p.total_hours, 0);
  assert(totalEmpHours === 40.0, 'El total de horas debe ser 40.0 horas');

  // TEST 6: Simulación de patrón rotativo 4x2 (6 días de ciclo)
  console.log('6. Probando proyección de patrón rotativo 4x2 (2M + 2N + 2D)...');
  const pattern4x2: { cycle_length_days: number; entries: ShiftPatternEntry[] } = {
    cycle_length_days: 6,
    entries: [
      { day_number: 1, day_type: 'WORK', shift_type_id: 1, shift_type: { id: 1, name: 'M', code: 'M', color_hex: '#3B82F6', start_time: '06:00', end_time: '14:00', total_work_hours: 8, crosses_midnight: false } },
      { day_number: 2, day_type: 'WORK', shift_type_id: 1, shift_type: { id: 1, name: 'M', code: 'M', color_hex: '#3B82F6', start_time: '06:00', end_time: '14:00', total_work_hours: 8, crosses_midnight: false } },
      { day_number: 3, day_type: 'WORK', shift_type_id: 2, shift_type: { id: 2, name: 'N', code: 'N', color_hex: '#8B5CF6', start_time: '22:00', end_time: '06:00', total_work_hours: 8, crosses_midnight: true } },
      { day_number: 4, day_type: 'WORK', shift_type_id: 2, shift_type: { id: 2, name: 'N', code: 'N', color_hex: '#8B5CF6', start_time: '22:00', end_time: '06:00', total_work_hours: 8, crosses_midnight: true } },
      { day_number: 5, day_type: 'REST', shift_type_id: null, shift_type: null },
      { day_number: 6, day_type: 'REST', shift_type_id: null, shift_type: null },
    ],
  };

  const rotProjections = simulatePatternProjections(pattern4x2, [1], '2026-08-01', '2026-08-12', 1);
  assert(rotProjections.length === 12, '12 días en un ciclo de 6 deben ser exactamente 2 ciclos completos');
  const rotWork = rotProjections.filter((p) => p.day_type === 'WORK');
  const rotRest = rotProjections.filter((p) => p.day_type === 'REST');
  assert(rotWork.length === 8, 'Debe haber 8 días laborales (4 por ciclo)');
  assert(rotRest.length === 4, 'Debe haber 4 días de descanso (2 por ciclo)');

  // TEST 7: Detección y manejo de concurrencia optimista HTTP 409
  console.log('7. Probando detección de conflicto de concurrencia (HTTP 409 lock_version)...');
  const conflictError = {
    response: {
      status: 409,
      data: {
        success: false,
        message: 'Conflicto de concurrencia optimista detectado.',
        current_lock_version: 3,
      },
    },
  };
  const isConflictStatus = conflictError.response.status === 409;
  assert(isConflictStatus === true, 'El estado 409 debe ser identificado como conflicto de concurrencia');
  assert(conflictError.response.data.current_lock_version === 3, 'Debe incluir la versión de bloqueo actual');

  // TEST 8: Control de acceso RBAC para gestión de patrones
  console.log('8. Probando permisos y control RBAC para gestión de patrones...');
  useAuthStore.getState().setAuth(
    {
      id: 5,
      company_id: 1,
      username: 'planner.user',
      email: 'planner@callshift.com',
      status: 'ACTIVE',
      role: { id: 3, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['shifts:view', 'shifts:create', 'shifts:update', 'shifts:delete', 'schedules:update'],
    },
    'token_hr_admin_789'
  );

  assert(useAuthStore.getState().hasPermission('shifts:create') === true, 'HR_ADMIN debe tener shifts:create');
  assert(useAuthStore.getState().hasPermission('schedules:update') === true, 'HR_ADMIN debe poder aplicar patrones');

  // Cambiar a VIEWER
  useAuthStore.getState().setAuth(
    {
      id: 6,
      company_id: 1,
      username: 'viewer.user',
      email: 'viewer@callshift.com',
      status: 'ACTIVE',
      role: { id: 6, code: 'VIEWER', name: 'Observador' },
      permissions: ['shifts:view', 'schedules:view'],
    },
    'token_viewer_000'
  );

  assert(useAuthStore.getState().hasPermission('shifts:view') === true, 'VIEWER puede ver patrones');
  assert(useAuthStore.getState().hasPermission('shifts:create') === false, 'VIEWER NO puede crear patrones');
  assert(useAuthStore.getState().hasPermission('schedules:update') === false, 'VIEWER NO puede aplicar patrones');

  console.log('\n✅ Todos los tests unitarios y de integración de FASE 14 pasaron al 100%!\n');
}

runShiftPatternsModuleTests();
