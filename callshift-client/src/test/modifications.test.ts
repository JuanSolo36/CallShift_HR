import { z } from 'zod';

// Esquema de validación frontend para solicitud de modificación
const ModificationTypeSchema = z.enum([
  'SHIFT_SWAP',
  'SHIFT_CHANGE',
  'TIME_CHANGE',
  'WORKDAY_CHANGE',
  'DAY_OFF_CHANGE',
  'REST_DAY_CHANGE',
  'LEAVE_PERMISSION',
  'ABSENCE_COVERAGE',
  'ABSENCE',
  'ADMINISTRATIVE_ADJUSTMENT',
  'OTHER',
]);

const CreateModificationSchema = z.object({
  schedule_assignment_id: z.number().int().positive(),
  employee_id: z.number().int().positive(),
  modification_type: ModificationTypeSchema,
  reason: z.string().trim().min(5, 'El motivo debe tener al menos 5 caracteres'),
  shift_type_id: z.number().int().positive().nullable().optional(),
  start_time: z.string().optional().nullable(),
  end_time: z.string().optional().nullable(),
  total_hours: z.number().min(0).max(24).optional(),
});

function canUserModifySchedule(roleCode: string): boolean {
  const allowed = ['SUPER_ADMIN', 'HR_ADMIN', 'MANAGER', 'SUPERVISOR'];
  return allowed.includes(roleCode);
}

function getModificationModeNotice(versionStatus: string): { createsNewVersion: boolean; message: string } {
  if (['PUBLISHED', 'ARCHIVED'].includes(versionStatus)) {
    return {
      createsNewVersion: true,
      message: 'Esta versión es histórica. Se creará automáticamente un nuevo borrador (V_next) para aplicar la modificación.',
    };
  }
  return {
    createsNewVersion: false,
    message: 'La modificación se aplicará directamente sobre el borrador actual.',
  };
}

function validateEvidenceConstraints(file: { name: string; size: number; type: string }): { valid: boolean; error?: string } {
  const MAX_SIZE = 10 * 1024 * 1024; // 10MB
  const ALLOWED_MIMES = ['application/pdf', 'image/png', 'image/jpeg', 'image/jpg'];

  if (file.size > MAX_SIZE) {
    return { valid: false, error: 'El archivo excede el tamaño máximo permitido de 10 MB.' };
  }

  if (!ALLOWED_MIMES.includes(file.type)) {
    return { valid: false, error: 'Formato no permitido. Solo se aceptan PDF, PNG y JPG.' };
  }

  return { valid: true };
}

console.log('--- Starting Schedule Modifications Frontend Unit Tests (Fase 17) ---');

// Test 1: Valid payload validation
const validData = {
  schedule_assignment_id: 101,
  employee_id: 5,
  modification_type: 'SHIFT_CHANGE' as const,
  reason: 'Cambio de turno por rotación imprevista',
  shift_type_id: 2,
  total_hours: 8,
};
const parsed1 = CreateModificationSchema.safeParse(validData);
if (!parsed1.success) throw new Error('Test 1 Failed: Valid payload was rejected');
console.log('✓ Test 1: Valid modification payload is accepted');

// Test 2: Reason < 5 chars is rejected
const invalidReason = { ...validData, reason: '  abc ' };
const parsed2 = CreateModificationSchema.safeParse(invalidReason);
if (parsed2.success) throw new Error('Test 2 Failed: Short reason was accepted');
console.log('✓ Test 2: Reason with less than 5 non-whitespace chars is rejected');

// Test 3: Historical version creates new version notice (M1)
const noticePub = getModificationModeNotice('PUBLISHED');
if (!noticePub.createsNewVersion || !noticePub.message.includes('nuevo borrador')) {
  throw new Error('Test 3 Failed: Published version did not trigger new version notice');
}
const noticeDraft = getModificationModeNotice('DRAFT');
if (noticeDraft.createsNewVersion) {
  throw new Error('Test 3 Failed: Draft version incorrectly triggered new version mode');
}
console.log('✓ Test 3: Modification mode notice correctly differentiates PUBLISHED vs DRAFT');

// Test 4: Evidence file validation (PDF, PNG, JPG allowed, exe/txt rejected, >10MB rejected)
const validPdf = { name: 'reporte.pdf', size: 1024 * 500, type: 'application/pdf' };
const validPng = { name: 'foto.png', size: 1024 * 800, type: 'image/png' };
const invalidExe = { name: 'malware.exe', size: 1024 * 100, type: 'application/x-msdownload' };
const oversizedPdf = { name: 'huge.pdf', size: 11 * 1024 * 1024, type: 'application/pdf' };

if (!validateEvidenceConstraints(validPdf).valid) throw new Error('Test 4 Failed: Valid PDF was rejected');
if (!validateEvidenceConstraints(validPng).valid) throw new Error('Test 4 Failed: Valid PNG was rejected');
if (validateEvidenceConstraints(invalidExe).valid) throw new Error('Test 4 Failed: EXE file was accepted');
if (validateEvidenceConstraints(oversizedPdf).valid) throw new Error('Test 4 Failed: Oversized PDF was accepted');
console.log('✓ Test 4: Evidence validation strictly enforces MIME types and 10MB limit');

// Test 5: RBAC permissions (Viewer denied, HR_ADMIN/MANAGER allowed)
if (canUserModifySchedule('VIEWER')) throw new Error('Test 5 Failed: VIEWER was allowed to modify');
if (!canUserModifySchedule('HR_ADMIN')) throw new Error('Test 5 Failed: HR_ADMIN was denied');
if (!canUserModifySchedule('MANAGER')) throw new Error('Test 5 Failed: MANAGER was denied');
if (!canUserModifySchedule('SUPERVISOR')) throw new Error('Test 5 Failed: SUPERVISOR was denied');
console.log('✓ Test 5: RBAC policy allows admins/managers and strictly denies VIEWER');

console.log('--- ALL SCHEDULE MODIFICATIONS FRONTEND TESTS PASSED (100%) ---');
