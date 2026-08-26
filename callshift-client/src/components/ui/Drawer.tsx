import React, { useEffect } from 'react';
import { X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from './Button';

export interface DrawerProps {
  isOpen: boolean;
  onClose: () => void;
  title?: string;
  description?: string;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  children: React.ReactNode;
  footer?: React.ReactNode;
}

export const Drawer: React.FC<DrawerProps> = ({
  isOpen,
  onClose,
  title,
  description,
  size = 'md',
  children,
  footer,
}) => {
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if (e.key === 'Escape' && isOpen) {
        onClose();
      }
    };
    if (isOpen) {
      document.body.style.overflow = 'hidden';
      window.addEventListener('keydown', handleKeyDown);
    }
    return () => {
      document.body.style.overflow = 'unset';
      window.removeEventListener('keydown', handleKeyDown);
    };
  }, [isOpen, onClose]);

  if (!isOpen) return null;

  const sizes = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-xl',
    xl: 'max-w-3xl',
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      {/* Backdrop */}
      <div
        className="fixed inset-0 bg-surface-900/60 backdrop-blur-xs transition-opacity animate-in fade-in duration-300"
        onClick={onClose}
      />

      <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div
          role="dialog"
          aria-modal="true"
          className={cn(
            'w-screen bg-white shadow-2xl border-l border-surface-200 flex flex-col animate-in slide-in-from-right duration-300',
            sizes[size]
          )}
        >
          {/* Header */}
          <div className="p-6 border-b border-surface-100 flex items-start justify-between">
            <div>
              {title && <h2 className="text-lg font-semibold text-surface-900 tracking-tight">{title}</h2>}
              {description && <p className="text-xs text-surface-500 mt-1">{description}</p>}
            </div>
            <Button
              variant="ghost"
              size="sm"
              onClick={onClose}
              className="h-8 w-8 p-0 rounded-lg text-surface-400 hover:text-surface-600"
              aria-label="Cerrar panel"
            >
              <X className="w-4 h-4" />
            </Button>
          </div>

          {/* Content */}
          <div className="p-6 overflow-y-auto flex-1">{children}</div>

          {/* Footer */}
          {footer && <div className="p-4 sm:p-6 border-t border-surface-100 bg-surface-50 flex items-center justify-end gap-3">{footer}</div>}
        </div>
      </div>
    </div>
  );
};
