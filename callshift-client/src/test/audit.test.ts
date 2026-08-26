import { z } from 'zod';

const AuditActionSchema = z.enum([
  'LOGIN',
  'LOGOUT',
  'CREATE',
  'UPDATE',
  'DELETE',
  'GENERATE',
  'PUBLISH',
  'MODIFY',
  'EXPORT',
  'RESTORE',
]);

const AuditFiltersSchema = z.object({
  user_id: z.number().int().positive().optional(),
  action: AuditActionSchema.optional(),
  auditable_type: z.string().optional(),
  auditable_id: z.number().int().positive().optional(),
  date_from: z.string().optional(),
  date_to: z.string().optional(),
  search: z.string().optional(),
  page: z.number().int().positive().default(1),
  per_page: z.number().int().positive().max(100).default(25),
});

function getAuditActionColor(action: string): string {
  switch (action) {
    case 'CREATE':
    case 'RESTORE':
    case 'GENERATE':
      return 'green';
    case 'UPDATE':
    case 'MODIFY':
      return 'blue';
    case 'PUBLISH':
      return 'purple';
    case 'DELETE':
      return 'red';
    case 'LOGIN':
    case 'LOGOUT':
      return 'gray';
    case 'EXPORT':
      return 'indigo';
    default:
      return 'default';
  }
}

function canViewAuditLogs(roleCode: string): boolean {
  return ['SUPER_ADMIN', 'HR_ADMIN', 'MANAGER'].includes(roleCode);
}

function canExportAuditLogs(roleCode: string): boolean {
  return ['SUPER_ADMIN', 'HR_ADMIN', 'MANAGER'].includes(roleCode);
}

function sanitizeDisplayValue(key: string, value: any): string {
  const sensitive = ['password', 'token', 'secret', 'credentials'];
  if (sensitive.some((s) => key.toLowerCase().includes(s))) {
    return '•••••••• (Protegido)';
  }
  return String(value);
}

console.log('--- Starting Audit Logs Frontend Unit Tests (Fase 18) ---');

// Test 1: Valid filter schema parsing
const validFilters = {
  action: 'PUBLISH' as const,
  date_from: '2026-08-01',
  date_to: '2026-08-31',
  page: 1,
  per_page: 50,
};
const parsed1 = AuditFiltersSchema.safeParse(validFilters);
if (!parsed1.success) throw new Error('Test 1 Failed: Valid filters rejected');
console.log('✓ Test 1: Valid audit filters schema is accepted');

// Test 2: Invalid action rejected
const invalidFilters = { action: 'INVALID_ACTION' };
const parsed2 = AuditFiltersSchema.safeParse(invalidFilters);
if (parsed2.success) throw new Error('Test 2 Failed: Invalid action accepted');
console.log('✓ Test 2: Invalid audit action is rejected by schema');

// Test 3: Action color mapping works for all 10 actions
const actions = ['LOGIN', 'LOGOUT', 'CREATE', 'UPDATE', 'DELETE', 'GENERATE', 'PUBLISH', 'MODIFY', 'EXPORT', 'RESTORE'];
actions.forEach((act) => {
  const color = getAuditActionColor(act);
  if (!color || color === 'default') throw new Error(`Test 3 Failed: Color for action ${act} not mapped`);
});
console.log('✓ Test 3: Action color badge mapping covers all 10 standard audit actions');

// Test 4: RBAC view and export permissions
if (canViewAuditLogs('VIEWER')) throw new Error('Test 4 Failed: VIEWER allowed to view audit logs');
if (canViewAuditLogs('EMPLOYEE')) throw new Error('Test 4 Failed: EMPLOYEE allowed to view audit logs');
if (!canViewAuditLogs('HR_ADMIN')) throw new Error('Test 4 Failed: HR_ADMIN denied view audit logs');
if (!canExportAuditLogs('HR_ADMIN')) throw new Error('Test 4 Failed: HR_ADMIN denied export audit logs');
if (canExportAuditLogs('VIEWER')) throw new Error('Test 4 Failed: VIEWER allowed to export audit logs');
console.log('✓ Test 4: RBAC permissions correctly restrict view/export to admins/managers');

// Test 5: Sensitive display sanitization
const maskedPass = sanitizeDisplayValue('password', 'secret123');
const maskedToken = sanitizeDisplayValue('remember_token', 'xyz789');
const normalText = sanitizeDisplayValue('first_name', 'Carlos');

if (!maskedPass.includes('Protegido')) throw new Error('Test 5 Failed: Password not masked');
if (!maskedToken.includes('Protegido')) throw new Error('Test 5 Failed: Token not masked');
if (normalText !== 'Carlos') throw new Error('Test 5 Failed: Normal field was masked');
console.log('✓ Test 5: Sensitive credentials and tokens are strictly masked in UI presentation');

console.log('--- ALL AUDIT LOGS FRONTEND TESTS PASSED (100%) ---');
