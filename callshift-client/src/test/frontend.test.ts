import { useAuthStore } from '../stores/useAuthStore';
import { useUIStore } from '../stores/useUIStore';

// Simple lightweight assertion runner for headless verification
function assert(condition: boolean, message: string) {
  if (!condition) {
    throw new Error(`[Assertion Failed] ${message}`);
  }
}

export function runFrontendTests() {
  console.log('🧪 Starting Comprehensive Frontend Post-Audit Unit Tests...\n');

  // ==========================================
  // 1. TEST DE ESTADO INICIAL Y HELPERS
  // ==========================================
  console.log('1. Probando estado inicial y métodos auxiliares de useAuthStore...');
  const authStore = useAuthStore.getState();
  assert(typeof authStore.isAuthenticated === 'boolean', 'isAuthenticated debe ser booleano');
  assert(typeof authStore.hasPermission === 'function', 'hasPermission debe ser una función');
  assert(typeof authStore.hasRole === 'function', 'hasRole debe ser una función');

  // ==========================================
  // 2. TEST DE REVALIDACIÓN Y SUPER_ADMIN BYPASS
  // ==========================================
  console.log('2. Probando inicio de sesión, revalidación y bypass de SUPER_ADMIN...');
  useAuthStore.getState().setAuth(
    {
      id: 1,
      company_id: 1,
      username: 'superadmin',
      email: 'admin@callshift.com',
      status: 'ACTIVE',
      role: { id: 1, code: 'SUPER_ADMIN', name: 'Super Administrador' },
      permissions: ['*'],
      company: { id: 1, name: 'CallShift Corp', timezone: 'America/Bogota', country: 'COL' },
    },
    'token_sanctum_valid_123'
  );

  assert(useAuthStore.getState().isAuthenticated === true, 'El usuario debe estar autenticado');
  assert(useAuthStore.getState().token === 'token_sanctum_valid_123', 'El token debe estar almacenado');
  assert(useAuthStore.getState().hasRole('SUPER_ADMIN') === true, 'hasRole(SUPER_ADMIN) debe ser true');
  assert(useAuthStore.getState().hasPermission('any:unassigned:permission') === true, 'SUPER_ADMIN debe tener acceso a todo (*) ');

  // Simular actualización de sesión (setUser) tras revalidación con backend
  useAuthStore.getState().setUser({
    id: 1,
    company_id: 1,
    username: 'superadmin',
    email: 'admin@callshift.com',
    status: 'ACTIVE',
    role: { id: 1, code: 'SUPER_ADMIN', name: 'Super Administrador' },
    permissions: ['*'],
    company: { id: 1, name: 'CallShift Enterprise S.A.S.', timezone: 'America/Bogota', country: 'COL' },
  });
  assert(useAuthStore.getState().user?.company?.name === 'CallShift Enterprise S.A.S.', 'setUser debe sincronizar datos frescos del backend');

  // ==========================================
  // 3. TEST DE RBAC GRANULAR
  // ==========================================
  console.log('3. Probando resolución granular de roles y permisos (Supervisor)...');
  useAuthStore.getState().setAuth(
    {
      id: 2,
      company_id: 1,
      username: 'supervisor.turnos',
      email: 'supervisor@callshift.com',
      status: 'ACTIVE',
      role: { id: 4, code: 'SUPERVISOR', name: 'Supervisor de Operaciones' },
      permissions: ['schedules:view', 'schedules:update'],
    },
    'token_sanctum_supervisor_456'
  );

  assert(useAuthStore.getState().hasRole('SUPERVISOR') === true, 'Debe reconocer rol SUPERVISOR');
  assert(useAuthStore.getState().hasRole(['MANAGER', 'SUPERVISOR']) === true, 'Debe coincidir con arreglo de roles permitidos');
  assert(useAuthStore.getState().hasRole('HR_ADMIN') === false, 'NO debe coincidir con rol no asignado');
  assert(useAuthStore.getState().hasPermission('schedules:view') === true, 'Debe tener permiso schedules:view');
  assert(useAuthStore.getState().hasPermission('employees:delete') === false, 'NO debe tener permiso no concedido');

  // ==========================================
  // 4. TEST DE INTERCEPTOR 401 Y LIMPIEZA DE SESIÓN (clearAuth)
  // ==========================================
  console.log('4. Probando limpieza de sesión ante 401 / expiración de token...');
  useAuthStore.getState().clearAuth();
  assert(useAuthStore.getState().isAuthenticated === false, 'isAuthenticated debe ser false tras clearAuth');
  assert(useAuthStore.getState().user === null, 'user debe ser null tras clearAuth');
  assert(useAuthStore.getState().token === null, 'token debe ser null tras clearAuth');

  // ==========================================
  // 5. TEST DEL SISTEMA DE TOASTS EN UI STORE
  // ==========================================
  console.log('5. Probando despacho y eliminación de mensajes Toast en useUIStore...');
  const initialToasts = useUIStore.getState().toasts.length;
  useUIStore.getState().addToast({
    type: 'error',
    title: 'Sesión expirada',
    message: 'Su sesión ha caducado. Inicie sesión nuevamente.',
    duration: 0,
  });

  assert(useUIStore.getState().toasts.length === initialToasts + 1, 'El toast debe agregarse a la cola');
  const addedToast = useUIStore.getState().toasts[useUIStore.getState().toasts.length - 1];
  assert(addedToast.title === 'Sesión expirada', 'El título del toast debe coincidir');

  useUIStore.getState().removeToast(addedToast.id);
  assert(useUIStore.getState().toasts.length === initialToasts, 'El toast debe eliminarse correctamente');

  console.log('\n✅ Todos los tests unitarios y de integración de FASE 5 pasaron al 100%!\n');
}

runFrontendTests();
