import { z } from 'zod';
import { useAuthStore } from '../stores/useAuthStore';

// Simple lightweight assertion runner
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

const companySchema = z.object({
  name: z.string().min(2, 'El nombre comercial es obligatorio.'),
  legal_name: z.string().min(2, 'La razón social es obligatoria.'),
  tax_id: z.string().min(3, 'El número de identificación tributaria (NIT) es obligatorio.'),
  slug: z.string().optional().nullable(),
  email: z.string().email('Debe ingresar un correo electrónico válido.'),
  phone: z.string().optional().nullable(),
  address: z.string().optional().nullable(),
  city: z.string().optional().nullable(),
  country: z.string().length(3, 'El código de país debe ser ISO Alpha-3 (ej. COL).'),
  timezone: z.string().min(2, 'La zona horaria es obligatoria.'),
  currency: z.string().min(2, 'La moneda es obligatoria.'),
  date_format: z.string().min(2, 'El formato de fecha es obligatorio.'),
  logo: z.string().optional().nullable(),
  primary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Formato hexadecimal inválido.'),
  secondary_color: z.string().regex(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/, 'Formato hexadecimal inválido.'),
});

export function runCompanyModuleTests() {
  console.log('🧪 Starting FASE 7 — Company & System Settings Unit Tests...\n');

  // 1. Validar schema con datos válidos
  console.log('1. Probando validación de formulario de empresa (Zod)...');
  const validData = {
    name: 'CallShift Enterprise S.A.S.',
    legal_name: 'CallShift Technologies S.A.S.',
    tax_id: '901.845.120-4',
    slug: 'callshift-enterprise',
    email: 'contacto@callshift.com',
    phone: '+57 (601) 745-9000',
    address: 'Av. El Dorado #68C-61, Torre Empresarial, Piso 10',
    city: 'Bogotá',
    country: 'COL',
    timezone: 'America/Bogota',
    currency: 'COP',
    date_format: 'YYYY-MM-DD',
    primary_color: '#0284c7',
    secondary_color: '#0f172a',
  };
  const parseResult = companySchema.safeParse(validData);
  assert(parseResult.success === true, 'El schema debe aceptar datos corporativos válidos');

  // 2. Validar rechazo por código de color hexadecimal inválido
  console.log('2. Probando rechazo de colores hexadecimales inválidos...');
  const invalidColorData = {
    ...validData,
    primary_color: 'invalid-hex-color',
  };
  const invalidColorResult = companySchema.safeParse(invalidColorData);
  assert(invalidColorResult.success === false, 'Debe rechazar un color no hexadecimal');

  // 3. Validar rechazo por código de país no Alpha-3
  console.log('3. Probando rechazo de código de país no Alpha-3...');
  const invalidCountryData = {
    ...validData,
    country: 'COLOMBIA', // Debería ser 'COL'
  };
  const invalidCountryResult = companySchema.safeParse(invalidCountryData);
  assert(invalidCountryResult.success === false, 'Debe rechazar códigos de país que no tengan 3 caracteres');

  // 4. Validar permisos de UI para configuración en useAuthStore
  console.log('4. Probando permisos de UI para edición de empresa (HR_ADMIN)...');
  useAuthStore.getState().setAuth(
    {
      id: 20,
      company_id: 1,
      username: 'hr.admin',
      email: 'hr.admin@callshift.com',
      status: 'ACTIVE',
      role: { id: 2, code: 'HR_ADMIN', name: 'Administrador de RRHH' },
      permissions: ['company:view', 'company:update', 'settings:manage'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_hr_admin_settings'
  );

  assert(useAuthStore.getState().hasPermission('company:view') === true, 'HR_ADMIN debe tener company:view');
  assert(useAuthStore.getState().hasPermission('company:update') === true, 'HR_ADMIN debe tener company:update');
  assert(useAuthStore.getState().hasPermission('settings:manage') === true, 'HR_ADMIN debe tener settings:manage');

  // 5. Validar denegación de UI para empleado sin permisos de configuración
  console.log('5. Probando denegación de UI para empleado estándar...');
  useAuthStore.getState().setAuth(
    {
      id: 88,
      company_id: 1,
      username: 'employee.standard',
      email: 'emp@callshift.com',
      status: 'ACTIVE',
      role: { id: 5, code: 'EMPLOYEE', name: 'Empleado' },
      permissions: [],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_emp_settings'
  );

  assert(useAuthStore.getState().hasPermission('company:update') === false, 'EMPLOYEE NO debe tener company:update');
  assert(useAuthStore.getState().hasPermission('settings:manage') === false, 'EMPLOYEE NO debe tener settings:manage');

  console.log('\n✅ Todos los tests del módulo de Empresa de FASE 7 pasaron exitosamente (100%)!\n');
}

runCompanyModuleTests();
