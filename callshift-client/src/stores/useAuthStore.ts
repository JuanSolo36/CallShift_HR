import { create } from 'zustand';
import type { UserSession } from '@/types/auth.types';

interface AuthState {
  user: UserSession | null;
  token: string | null;
  isAuthenticated: boolean;
  isLoading: boolean;
  setAuth: (user: UserSession, token: string) => void;
  setUser: (user: UserSession) => void;
  clearAuth: () => void;
  setLoading: (isLoading: boolean) => void;
  hasPermission: (permissionCode: string) => boolean;
  hasRole: (roleCode: string | string[]) => boolean;
}

const STORAGE_TOKEN_KEY = 'callshift_auth_token';
const STORAGE_USER_KEY = 'callshift_user_session';

const getInitialToken = (): string | null => {
  if (typeof window === 'undefined' || !window.localStorage) return null;
  try {
    return localStorage.getItem(STORAGE_TOKEN_KEY);
  } catch {
    return null;
  }
};

const getInitialUser = (): UserSession | null => {
  if (typeof window === 'undefined' || !window.localStorage) return null;
  try {
    const raw = localStorage.getItem(STORAGE_USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
};

export const useAuthStore = create<AuthState>((set, get) => ({
  user: getInitialUser(),
  token: getInitialToken(),
  isAuthenticated: !!getInitialToken(),
  isLoading: false,

  setAuth: (user, token) => {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        localStorage.setItem(STORAGE_TOKEN_KEY, token);
        localStorage.setItem(STORAGE_USER_KEY, JSON.stringify(user));
      } catch (e) {
        console.error('Failed to persist auth to localStorage', e);
      }
    }
    set({ user, token, isAuthenticated: true, isLoading: false });
  },

  setUser: (user) => {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        localStorage.setItem(STORAGE_USER_KEY, JSON.stringify(user));
      } catch (e) {
        console.error('Failed to persist user to localStorage', e);
      }
    }
    set({ user });
  },

  clearAuth: () => {
    if (typeof window !== 'undefined' && window.localStorage) {
      try {
        localStorage.removeItem(STORAGE_TOKEN_KEY);
        localStorage.removeItem(STORAGE_USER_KEY);
      } catch (e) {
        console.error('Failed to clear auth from localStorage', e);
      }
    }
    set({ user: null, token: null, isAuthenticated: false, isLoading: false });
  },

  setLoading: (isLoading) => set({ isLoading }),

  hasPermission: (permissionCode) => {
    const user = get().user;
    if (!user) return false;
    if (user.role?.code === 'SUPER_ADMIN' || user.permissions.includes('*')) return true;
    return user.permissions.includes(permissionCode);
  },

  hasRole: (roleCode) => {
    const user = get().user;
    if (!user || !user.role) return false;
    if (Array.isArray(roleCode)) {
      return roleCode.includes(user.role.code);
    }
    return user.role.code === roleCode;
  },
}));
