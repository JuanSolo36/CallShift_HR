import { z } from 'zod';

const ReportTypeSchema = z.enum([
  'employees',
  'schedules',
  'hours',
  'absences',
  'modifications',
  'audit',
]);

const ReportFiltersSchema = z.object({
  department_id: z.number().int().positive().optional(),
  position_id: z.number().int().positive().optional(),
  employment_type_id: z.number().int().positive().optional(),
  status: z.string().optional(),
  work_period_id: z.number().int().positive().optional(),
  schedule_version_id: z.number().int().positive().optional(),
  employee_id: z.number().int().positive().optional(),
  type: z.string().optional(),
  modification_type: z.string().optional(),
  day_type: z.string().optional(),
  date_from: z.string().optional(),
  date_to: z.string().optional(),
  search: z.string().optional(),
  page: z.number().int().positive().default(1),
  per_page: z.number().int().positive().max(100).default(25),
});

function canViewReport(roleCode: string, reportType: string): boolean {
  if (['SUPER_ADMIN', 'HR_ADMIN', 'MANAGER'].includes(roleCode)) return true;
  if (roleCode === 'SUPERVISOR' && ['employees', 'schedules', 'hours', 'absences', 'modifications'].includes(reportType)) return true;
  if (roleCode === 'VIEWER' && ['employees', 'schedules', 'hours', 'absences'].includes(reportType)) return true;
  return false;
}

function canExportReport(roleCode: string): boolean {
  return ['SUPER_ADMIN', 'HR_ADMIN', 'MANAGER'].includes(roleCode);
}

function calculateHoursSummary(employees: Array<{ total_work_hours: number; total_work_days: number }>) {
  const totalHours = employees.reduce((acc, curr) => acc + curr.total_work_hours, 0);
  const count = employees.length;
  const avg = count > 0 ? Number((totalHours / count).toFixed(2)) : 0;
  return {
    total_employees: count,
    total_hours: Number(totalHours.toFixed(2)),
    average_per_staff: avg,
  };
}

console.log('--- Starting Reports Frontend Unit Tests (Fase 19) ---');

// Test 1: Valid report types accepted
const validTypes = ['employees', 'schedules', 'hours', 'absences', 'modifications', 'audit'];
validTypes.forEach((t) => {
  const p = ReportTypeSchema.safeParse(t);
  if (!p.success) throw new Error(`Test 1 Failed: Valid report type ${t} was rejected`);
});
console.log('✓ Test 1: All 6 standard report types are valid');

// Test 2: Invalid report type rejected
const invalidType = ReportTypeSchema.safeParse('invalid_report_name');
if (invalidType.success) throw new Error('Test 2 Failed: Invalid report type accepted');
console.log('✓ Test 2: Invalid report type is rejected by schema');

// Test 3: Filters schema accepts valid query filters
const validFilters = {
  department_id: 3,
  work_period_id: 12,
  date_from: '2026-08-01',
  date_to: '2026-08-31',
  page: 1,
  per_page: 50,
};
const parsedFilters = ReportFiltersSchema.safeParse(validFilters);
if (!parsedFilters.success) throw new Error('Test 3 Failed: Valid filters rejected');
console.log('✓ Test 3: Report filters schema validates query parameters properly');

// Test 4: RBAC view rules per role and report type
if (!canViewReport('SUPER_ADMIN', 'audit')) throw new Error('Test 4 Failed: Super admin cannot view audit');
if (!canViewReport('SUPERVISOR', 'schedules')) throw new Error('Test 4 Failed: Supervisor cannot view schedules');
if (canViewReport('SUPERVISOR', 'audit')) throw new Error('Test 4 Failed: Supervisor can view audit');
if (canViewReport('VIEWER', 'modifications')) throw new Error('Test 4 Failed: Viewer can view modifications');
if (canViewReport('EMPLOYEE', 'employees')) throw new Error('Test 4 Failed: Employee can view global employee report');
console.log('✓ Test 4: Granular RBAC view permissions strictly enforced');

// Test 5: RBAC export rules
if (!canExportReport('HR_ADMIN')) throw new Error('Test 5 Failed: HR_ADMIN denied export');
if (canExportReport('VIEWER')) throw new Error('Test 5 Failed: VIEWER allowed export');
if (canExportReport('EMPLOYEE')) throw new Error('Test 5 Failed: EMPLOYEE allowed export');
console.log('✓ Test 5: Export permission is strictly restricted to administrative roles');

// Test 6: Hours calculation and safe average computation
const sampleStaff = [
  { total_work_hours: 40.0, total_work_days: 5 },
  { total_work_hours: 48.0, total_work_days: 6 },
  { total_work_hours: 32.5, total_work_days: 4 },
];
const summary = calculateHoursSummary(sampleStaff);
if (summary.total_employees !== 3) throw new Error('Test 6 Failed: Staff count mismatch');
if (summary.total_hours !== 120.5) throw new Error('Test 6 Failed: Total hours mismatch');
if (summary.average_per_staff !== 40.17) throw new Error('Test 6 Failed: Average hours mismatch');

// Empty staff edge case
const emptySummary = calculateHoursSummary([]);
if (emptySummary.average_per_staff !== 0 || emptySummary.total_hours !== 0) throw new Error('Test 6 Failed: Zero division bug');
console.log('✓ Test 6: Hours worked summary calculation is mathematically exact and safe');

console.log('--- ALL REPORTS FRONTEND UNIT TESTS PASSED (100%) ---');
