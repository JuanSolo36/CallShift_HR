import React from 'react';
import { AlertCircle, CheckCircle2, AlertTriangle, Info, X } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface AlertProps extends React.HTMLAttributes<HTMLDivElement> {
  variant?: 'success' | 'warning' | 'error' | 'info';
  title?: string;
  onClose?: () => void;
}

export const Alert: React.FC<AlertProps> = ({
  className,
  variant = 'info',
  title,
  children,
  onClose,
  ...props
}) => {
  const icons = {
    success: <CheckCircle2 className="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" />,
    warning: <AlertTriangle className="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />,
    error: <AlertCircle className="w-4 h-4 text-rose-600 shrink-0 mt-0.5" />,
    info: <Info className="w-4 h-4 text-sky-600 shrink-0 mt-0.5" />,
  };

  const variants = {
    success: 'bg-emerald-50 text-emerald-900 border-emerald-200',
    warning: 'bg-amber-50 text-amber-900 border-amber-200',
    error: 'bg-rose-50 text-rose-900 border-rose-200',
    info: 'bg-sky-50 text-sky-900 border-sky-200',
  };

  return (
    <div
      role="alert"
      className={cn('flex items-start gap-3 p-4 rounded-xl border text-xs leading-relaxed text-left', variants[variant], className)}
      {...props}
    >
      {icons[variant]}
      <div className="flex-1 space-y-0.5">
        {title && <h5 className="font-semibold">{title}</h5>}
        <div className="text-surface-700">{children}</div>
      </div>

      {onClose && (
        <button
          type="button"
          onClick={onClose}
          className="text-surface-400 hover:text-surface-600 -mr-1 -mt-1 p-1 rounded-md"
          aria-label="Cerrar alerta"
        >
          <X className="w-3.5 h-3.5" />
        </button>
      )}
    </div>
  );
};
