import React, { useState } from 'react';
import { useUIStore } from '@/stores/useUIStore';
import { useAuthStore } from '@/stores/useAuthStore';
import { Menu, Bell, Search, LogOut, KeyRound } from 'lucide-react';
import { Avatar } from '../ui/Avatar';
import { Badge } from '../ui/Badge';

export const Topbar: React.FC = () => {
  const { toggleSidebar } = useUIStore();
  const { user, clearAuth } = useAuthStore();
  const [dropdownOpen, setDropdownOpen] = useState(false);

  return (
    <header className="sticky top-0 z-30 h-16 bg-white border-b border-surface-200 flex items-center justify-between px-4 sm:px-6 shadow-xs select-none">
      {/* Left side: Hamburger + Search */}
      <div className="flex items-center gap-4 flex-1">
        <button
          onClick={toggleSidebar}
          className="lg:hidden p-2 rounded-lg text-surface-500 hover:text-surface-700 hover:bg-surface-100 transition-colors"
          aria-label="Abrir menú"
        >
          <Menu className="w-5 h-5" />
        </button>

        {/* Global Search Bar */}
        <div className="relative hidden md:flex items-center max-w-xs w-full">
          <Search className="w-4 h-4 absolute left-3 text-surface-400 pointer-events-none" />
          <input
            type="search"
            placeholder="Buscar empleados, turnos, horarios..."
            className="w-full bg-surface-50 border border-surface-200 rounded-lg pl-9 pr-3 py-1.5 text-xs text-surface-800 placeholder:text-surface-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:bg-white transition-all"
          />
        </div>
      </div>

      {/* Right side: Tenant Badge, Notifications, User Menu */}
      <div className="flex items-center gap-3">
        {/* Company indicator */}
        {user?.company && (
          <div className="hidden sm:flex items-center gap-2 px-2.5 py-1 rounded-lg bg-surface-50 border border-surface-200 text-xs">
            <span className="w-2 h-2 rounded-full bg-brand-500"></span>
            <span className="font-semibold text-surface-700 truncate max-w-[140px]">{user.company.name}</span>
          </div>
        )}

        {/* Notification Bell */}
        <button
          className="relative p-2 rounded-lg text-surface-500 hover:text-surface-700 hover:bg-surface-100 transition-colors"
          aria-label="Notificaciones"
        >
          <Bell className="w-4 h-4" />
          <span className="absolute top-1.5 right-1.5 w-2 h-2 rounded-full bg-brand-600 ring-2 ring-white"></span>
        </button>

        <div className="h-6 w-px bg-surface-200 mx-1 hidden sm:block"></div>

        {/* User Dropdown */}
        <div className="relative">
          <button
            onClick={() => setDropdownOpen(!dropdownOpen)}
            className="flex items-center gap-2.5 p-1 rounded-lg hover:bg-surface-100 transition-colors"
            aria-expanded={dropdownOpen}
          >
            <Avatar name={user?.employee?.full_name || user?.username || 'Usuario'} size="sm" status="online" />
            <div className="hidden sm:block text-left">
              <div className="text-xs font-semibold text-surface-900 leading-tight">
                {user?.employee?.full_name || user?.username}
              </div>
              <div className="text-[10px] text-surface-400 font-medium">
                {user?.role?.name || 'Usuario'}
              </div>
            </div>
          </button>

          {/* Dropdown Menu */}
          {dropdownOpen && (
            <>
              <div className="fixed inset-0 z-40" onClick={() => setDropdownOpen(false)} />
              <div className="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-lg border border-surface-200 py-1.5 z-50 text-xs animate-in fade-in zoom-in-95 duration-150">
                <div className="px-4 py-2 border-b border-surface-100">
                  <div className="font-semibold text-surface-900 truncate">
                    {user?.employee?.full_name || user?.username}
                  </div>
                  <div className="text-surface-400 text-[11px] truncate">{user?.email}</div>
                  <div className="mt-1.5">
                    <Badge variant="brand" size="sm">
                      {user?.role?.name}
                    </Badge>
                  </div>
                </div>

                <div className="py-1">
                  <button
                    onClick={() => {
                      setDropdownOpen(false);
                      // Abrir modal o ruta de cambio de contraseña
                    }}
                    className="w-full flex items-center gap-2.5 px-4 py-2 text-surface-700 hover:bg-surface-50 text-left transition-colors"
                  >
                    <KeyRound className="w-3.5 h-3.5 text-surface-400" />
                    <span>Cambiar Contraseña</span>
                  </button>
                </div>

                <div className="border-t border-surface-100 pt-1">
                  <button
                    onClick={() => {
                      setDropdownOpen(false);
                      clearAuth();
                    }}
                    className="w-full flex items-center gap-2.5 px-4 py-2 text-rose-600 hover:bg-rose-50 text-left transition-colors font-medium"
                  >
                    <LogOut className="w-3.5 h-3.5" />
                    <span>Cerrar Sesión</span>
                  </button>
                </div>
              </div>
            </>
          )}
        </div>
      </div>
    </header>
  );
};
