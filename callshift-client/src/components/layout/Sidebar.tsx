import React from 'react';
import { NavLink, useLocation } from 'react-router-dom';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';
import { cn } from '@/lib/utils';
import {
  LayoutDashboard,
  Users,
  Building2,
  Briefcase,
  FileText,
  CalendarDays,
  Clock,
  Sliders,
  CalendarCheck,
  PlaneTakeoff,
  History,
  BarChart3,
  ShieldCheck,
  Settings,
  Layers,
  ChevronLeft,
  ChevronRight,
  LogOut,
} from 'lucide-react';
import { Button } from '../ui/Button';

interface NavItem {
  name: string;
  href: string;
  icon: React.ReactNode;
  permission?: string;
  badge?: string;
}

interface NavGroup {
  title?: string;
  items: NavItem[];
}

export const Sidebar: React.FC = () => {
  const { sidebarCollapsed, toggleCollapse, sidebarOpen, setSidebarOpen } = useUIStore();
  const { user, hasPermission, clearAuth } = useAuthStore();
  const location = useLocation();

  const navigation: NavGroup[] = [
    {
      items: [
        { name: 'Dashboard', href: '/dashboard', icon: <LayoutDashboard className="w-4 h-4 shrink-0" /> },
      ],
    },
    {
      title: 'Recursos Humanos',
      items: [
        { name: 'Empleados', href: '/employees', icon: <Users className="w-4 h-4 shrink-0" />, permission: 'employees:view' },
        { name: 'Departamentos', href: '/departments', icon: <Building2 className="w-4 h-4 shrink-0" />, permission: 'organization:view' },
        { name: 'Cargos', href: '/positions', icon: <Briefcase className="w-4 h-4 shrink-0" />, permission: 'organization:view' },
        { name: 'Tipos de Contrato', href: '/employment-types', icon: <FileText className="w-4 h-4 shrink-0" />, permission: 'organization:view' },
      ],
    },
    {
      title: 'Planificación',
      items: [
        { name: 'Periodos Laborales', href: '/work-periods', icon: <CalendarDays className="w-4 h-4 shrink-0" />, permission: 'schedules:view' },
        { name: 'Malla de Horarios', href: '/schedules', icon: <Layers className="w-4 h-4 shrink-0" />, permission: 'schedules:view' },
        { name: 'Tipos de Turno', href: '/shift-types', icon: <Clock className="w-4 h-4 shrink-0" />, permission: 'shifts:view' },
        { name: 'Patrones y Plantillas', href: '/shift-patterns', icon: <Sliders className="w-4 h-4 shrink-0" />, permission: 'shifts:view' },
        { name: 'Disponibilidad', href: '/availability', icon: <CalendarCheck className="w-4 h-4 shrink-0" />, permission: 'availability:view' },
        { name: 'Reglas de Jornada', href: '/business-rules', icon: <Sliders className="w-4 h-4 shrink-0" />, permission: 'settings:manage' },
      ],
    },
    {
      title: 'Novedades',
      items: [
        { name: 'Ausencias y Permisos', href: '/absences', icon: <PlaneTakeoff className="w-4 h-4 shrink-0" />, permission: 'absences:view' },
        { name: 'Modificaciones', href: '/modifications', icon: <History className="w-4 h-4 shrink-0" />, permission: 'schedules:modify' },
      ],
    },
    {
      title: 'Reportes y Auditoría',
      items: [
        { name: 'Reportes', href: '/reports', icon: <BarChart3 className="w-4 h-4 shrink-0" />, permission: 'reports:view' },
        { name: 'Auditoría Forense', href: '/audit-logs', icon: <ShieldCheck className="w-4 h-4 shrink-0" />, permission: 'audit:view' },
      ],
    },
    {
      title: 'Configuración',
      items: [
        { name: 'Ajustes de Empresa', href: '/settings', icon: <Settings className="w-4 h-4 shrink-0" />, permission: 'settings:manage' },
      ],
    },
  ];

  return (
    <>
      {/* Mobile Backdrop */}
      {sidebarOpen && (
        <div
          className="fixed inset-0 z-40 bg-surface-900/60 backdrop-blur-xs lg:hidden"
          onClick={() => setSidebarOpen(false)}
        />
      )}

      {/* Sidebar Container */}
      <aside
        className={cn(
          'fixed top-0 bottom-0 left-0 z-40 bg-white border-r border-surface-200 transition-all duration-300 flex flex-col justify-between select-none shadow-xs',
          sidebarCollapsed ? 'w-18' : 'w-64',
          sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        )}
      >
        {/* Header / Logo */}
        <div>
          <div className="h-16 flex items-center justify-between px-4 border-b border-surface-100">
            <div className="flex items-center gap-3 overflow-hidden">
              <div className="w-9 h-9 rounded-xl bg-brand-600 text-white font-bold text-sm flex items-center justify-center shadow-xs shrink-0 tracking-tight">
                CS
              </div>
              {!sidebarCollapsed && (
                <div className="min-w-0">
                  <div className="text-sm font-bold tracking-tight text-surface-900 leading-none">CallShift HR</div>
                  <div className="text-[10px] text-surface-400 font-medium truncate mt-1">
                    {user?.company?.name || 'Recursos Humanos'}
                  </div>
                </div>
              )}
            </div>

            {/* Collapse toggle (desktop only) */}
            <button
              onClick={toggleCollapse}
              className="hidden lg:flex p-1 rounded-md text-surface-400 hover:text-surface-600 hover:bg-surface-100 transition-colors"
              aria-label={sidebarCollapsed ? 'Expandir menú' : 'Contraer menú'}
            >
              {sidebarCollapsed ? <ChevronRight className="w-4 h-4" /> : <ChevronLeft className="w-4 h-4" />}
            </button>
          </div>

          {/* Navigation Links */}
          <nav className="p-3 space-y-6 overflow-y-auto max-h-[calc(100vh-8rem)]">
            {navigation.map((group, groupIdx) => {
              // Filtrar ítems según permisos
              const visibleItems = group.items.filter(
                (item) => !item.permission || hasPermission(item.permission)
              );

              if (visibleItems.length === 0) return null;

              return (
                <div key={groupIdx} className="space-y-1">
                  {group.title && !sidebarCollapsed && (
                    <div className="px-3 text-[10px] font-semibold uppercase tracking-wider text-surface-400 mb-1.5">
                      {group.title}
                    </div>
                  )}

                  {visibleItems.map((item) => {
                    const isActive = location.pathname === item.href || location.pathname.startsWith(`${item.href}/`);
                    return (
                      <NavLink
                        key={item.href}
                        to={item.href}
                        onClick={() => setSidebarOpen(false)}
                        className={cn(
                          'flex items-center gap-3 px-3 py-2 rounded-lg text-xs font-medium transition-all group',
                          isActive
                            ? 'bg-brand-50 text-brand-700 font-semibold shadow-xs'
                            : 'text-surface-600 hover:text-surface-900 hover:bg-surface-100/80',
                          sidebarCollapsed && 'justify-center px-0'
                        )}
                        title={sidebarCollapsed ? item.name : undefined}
                      >
                        <span className={cn('transition-colors', isActive ? 'text-brand-600' : 'text-surface-400 group-hover:text-surface-600')}>
                          {item.icon}
                        </span>
                        {!sidebarCollapsed && <span className="truncate">{item.name}</span>}
                        {!sidebarCollapsed && item.badge && (
                          <span className="ml-auto px-1.5 py-0.5 rounded-full text-[10px] bg-brand-100 text-brand-700 font-bold">
                            {item.badge}
                          </span>
                        )}
                      </NavLink>
                    );
                  })}
                </div>
              );
            })}
          </nav>
        </div>

        {/* Footer info & Logout */}
        <div className="p-3 border-t border-surface-100">
          <Button
            variant="ghost"
            size="sm"
            onClick={() => clearAuth()}
            className={cn('w-full text-rose-600 hover:bg-rose-50 hover:text-rose-700 justify-start', sidebarCollapsed && 'justify-center px-0')}
            title="Cerrar sesión"
          >
            <LogOut className="w-4 h-4 shrink-0" />
            {!sidebarCollapsed && <span className="ml-2 text-xs">Cerrar Sesión</span>}
          </Button>
        </div>
      </aside>
    </>
  );
};
