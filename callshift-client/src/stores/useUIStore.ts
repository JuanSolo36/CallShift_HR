import { create } from 'zustand';

export interface ToastMessage {
  id: string;
  type: 'success' | 'warning' | 'error' | 'info';
  title?: string;
  message: string;
  duration?: number;
}

interface UIState {
  sidebarOpen: boolean;
  sidebarCollapsed: boolean;
  toasts: ToastMessage[];
  toggleSidebar: () => void;
  toggleCollapse: () => void;
  setSidebarOpen: (open: boolean) => void;
  addToast: (toast: Omit<ToastMessage, 'id'>) => void;
  removeToast: (id: string) => void;
}

export const useUIStore = create<UIState>((set) => ({
  sidebarOpen: false,
  sidebarCollapsed: false,
  toasts: [],

  toggleSidebar: () => set((state) => ({ sidebarOpen: !state.sidebarOpen })),
  toggleCollapse: () => set((state) => ({ sidebarCollapsed: !state.sidebarCollapsed })),
  setSidebarOpen: (open) => set({ sidebarOpen: open }),

  addToast: (toast) => {
    const id = Math.random().toString(36).substring(2, 9);
    const newToast: ToastMessage = { ...toast, id };
    
    set((state) => ({ toasts: [...state.toasts, newToast] }));

    const duration = toast.duration ?? 4000;
    if (duration > 0) {
      setTimeout(() => {
        set((state) => ({ toasts: state.toasts.filter((t) => t.id !== id) }));
      }, duration);
    }
  },

  removeToast: (id) => {
    set((state) => ({ toasts: state.toasts.filter((t) => t.id !== id) }));
  },
}));
