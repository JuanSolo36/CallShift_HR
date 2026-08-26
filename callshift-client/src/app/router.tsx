import { lazy, Suspense } from 'react';
import { createBrowserRouter, Navigate } from 'react-router-dom';
import { AppLayout } from '@/components/layout/AppLayout';
import { ProtectedRoute, PublicRoute } from '@/components/layout/ProtectedRoute';
import { LoadingState } from '@/components/feedback/LoadingState';

// Lazy loading de páginas para optimización de bundle inicial (Code Splitting)
const LoginPage = lazy(() =>
  import('@/features/auth/pages/LoginPage').then((m) => ({ default: m.LoginPage }))
);
const DashboardPage = lazy(() =>
  import('./DashboardPage').then((m) => ({ default: m.DashboardPage }))
);
const UsersPage = lazy(() =>
  import('@/features/users/pages/UsersPage').then((m) => ({ default: m.UsersPage }))
);
const CompanySettingsPage = lazy(() =>
  import('@/features/company/pages/CompanySettingsPage').then((m) => ({ default: m.CompanySettingsPage }))
);
const DepartmentsPage = lazy(() =>
  import('@/features/organization/pages/DepartmentsPage').then((m) => ({ default: m.DepartmentsPage }))
);
const PositionsPage = lazy(() =>
  import('@/features/organization/pages/PositionsPage').then((m) => ({ default: m.PositionsPage }))
);
const EmploymentTypesPage = lazy(() =>
  import('@/features/organization/pages/EmploymentTypesPage').then((m) => ({ default: m.EmploymentTypesPage }))
);
const EmployeesPage = lazy(() =>
  import('@/features/employees/pages/EmployeesPage').then((m) => ({ default: m.EmployeesPage }))
);
const ShiftTypesPage = lazy(() =>
  import('@/features/shifts/pages/ShiftTypesPage').then((m) => ({ default: m.ShiftTypesPage }))
);
const ShiftPatternsPage = lazy(() =>
  import('@/features/shifts/pages/ShiftPatternsPage').then((m) => ({ default: m.ShiftPatternsPage }))
);
const WorkPeriodsPage = lazy(() =>
  import('@/features/work-periods/pages/WorkPeriodsPage').then((m) => ({ default: m.WorkPeriodsPage }))
);
const ScheduleEditorPage = lazy(() =>
  import('@/features/schedules/pages/ScheduleEditorPage').then((m) => ({ default: m.ScheduleEditorPage }))
);
const BusinessRulesPage = lazy(() =>
  import('@/features/company/pages/BusinessRulesPage').then((m) => ({ default: m.BusinessRulesPage }))
);

const PageSuspenseFallback = (
  <div className="flex-1 flex items-center justify-center min-h-[50vh]">
    <LoadingState message="Cargando módulo..." />
  </div>
);

export const router = createBrowserRouter([
  // Public Routes (Accessible only when logged out)
  {
    element: <PublicRoute />,
    children: [
      {
        path: '/login',
        element: (
          <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
            <LoginPage />
          </Suspense>
        ),
      },
    ],
  },

  // Protected Enterprise Routes (Require Authentication)
  {
    element: <ProtectedRoute />,
    children: [
      {
        element: <AppLayout />,
        children: [
          {
            path: '/',
            element: <Navigate to="/dashboard" replace />,
          },
          {
            path: '/dashboard',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <DashboardPage />
              </Suspense>
            ),
          },
          {
            path: '/users',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <UsersPage />
              </Suspense>
            ),
          },
          {
            path: '/departments',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <DepartmentsPage />
              </Suspense>
            ),
          },
          {
            path: '/positions',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <PositionsPage />
              </Suspense>
            ),
          },
          {
            path: '/employment-types',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <EmploymentTypesPage />
              </Suspense>
            ),
          },
          {
            path: '/employees',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <EmployeesPage />
              </Suspense>
            ),
          },
          {
            path: '/shift-types',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <ShiftTypesPage />
              </Suspense>
            ),
          },
          {
            path: '/shift-patterns',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <ShiftPatternsPage />
              </Suspense>
            ),
          },
          {
            path: '/work-periods',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <WorkPeriodsPage />
              </Suspense>
            ),
          },
          {
            path: '/schedules',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <ScheduleEditorPage />
              </Suspense>
            ),
          },
          {
            path: '/schedules/:periodId',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <ScheduleEditorPage />
              </Suspense>
            ),
          },
          {
            path: '/availability',
            element: <Navigate to="/dashboard" replace />,
          },
          {
            path: '/business-rules',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <BusinessRulesPage />
              </Suspense>
            ),
          },
          {
            path: '/absences',
            element: <Navigate to="/dashboard" replace />,
          },
          {
            path: '/modifications',
            element: <Navigate to="/dashboard" replace />,
          },
          {
            path: '/reports',
            element: <Navigate to="/dashboard" replace />,
          },
          {
            path: '/audit-logs',
            element: <Navigate to="/dashboard" replace />,
          },
          {
            path: '/settings',
            element: (
              <Suspense fallback={<PageSuspenseFallback.type {...PageSuspenseFallback.props} />}>
                <CompanySettingsPage />
              </Suspense>
            ),
          },
        ],
      },
    ],
  },

  // Fallback
  {
    path: '*',
    element: <Navigate to="/dashboard" replace />,
  },
]);
