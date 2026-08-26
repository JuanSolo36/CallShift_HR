import React from 'react';
import { Outlet } from 'react-router-dom';
import { Sidebar } from './Sidebar';
import { Topbar } from './Topbar';
import { ToastContainer } from '../feedback/Toast';
import { ErrorBoundary } from '../feedback/ErrorBoundary';
import { useUIStore } from '@/stores/useUIStore';
import { cn } from '@/lib/utils';

export const AppLayout: React.FC = () => {
  const { sidebarCollapsed } = useUIStore();

  return (
    <div className="min-h-screen bg-surface-50 flex flex-col antialiased">
      {/* Global Toast Notification Portal */}
      <ToastContainer />

      {/* Main Sidebar */}
      <Sidebar />

      {/* Main Content Area */}
      <div
        className={cn(
          'flex-1 flex flex-col transition-all duration-300',
          sidebarCollapsed ? 'lg:pl-18' : 'lg:pl-64'
        )}
      >
        <Topbar />

        <main className="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
          <ErrorBoundary>
            <Outlet />
          </ErrorBoundary>
        </main>
      </div>
    </div>
  );
};
