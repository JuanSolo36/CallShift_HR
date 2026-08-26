import React from 'react';
import { useUIStore, type ToastMessage } from '@/stores/useUIStore';
import { CheckCircle2, AlertTriangle, AlertCircle, Info, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export const ToastContainer: React.FC = () => {
  const { toasts, removeToast } = useUIStore();

  if (toasts.length === 0) return null;

  return (
    <div
      aria-live="polite"
      className="fixed bottom-4 right-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
    >
      {toasts.map((toast) => (
        <ToastItem key={toast.id} toast={toast} onClose={() => removeToast(toast.id)} />
      ))}
    </div>
  );
};

const ToastItem: React.FC<{ toast: ToastMessage; onClose: () => void }> = ({ toast, onClose }) => {
  const icons = {
    success: <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0" />,
    warning: <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0" />,
    error: <AlertCircle className="w-4 h-4 text-rose-600 shrink-0" />,
    info: <Info className="w-4 h-4 text-sky-600 shrink-0" />,
  };

  const borders = {
    success: 'border-l-emerald-500',
    warning: 'border-l-amber-500',
    error: 'border-l-rose-500',
    info: 'border-l-sky-500',
  };

  return (
    <div
      className={cn(
        'pointer-events-auto flex items-start gap-3 p-3.5 bg-white rounded-xl shadow-lg border border-surface-200 border-l-4 text-left animate-in slide-in-from-bottom-2 duration-200',
        borders[toast.type]
      )}
    >
      {icons[toast.type]}
      <div className="flex-1 space-y-0.5 min-w-0">
        {toast.title && <h6 className="text-xs font-semibold text-surface-900">{toast.title}</h6>}
        <p className="text-xs text-surface-600 break-words">{toast.message}</p>
      </div>
      <button
        onClick={onClose}
        className="text-surface-400 hover:text-surface-600 p-0.5 rounded-md shrink-0"
        aria-label="Cerrar notificación"
      >
        <X className="w-3.5 h-3.5" />
      </button>
    </div>
  );
};
