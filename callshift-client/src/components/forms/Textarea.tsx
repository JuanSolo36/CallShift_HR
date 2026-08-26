import React from 'react';
import { cn } from '@/lib/utils';

export interface TextareaProps extends React.TextareaHTMLAttributes<HTMLTextAreaElement> {
  label?: string;
  helperText?: string;
  error?: string;
}

export const Textarea = React.forwardRef<HTMLTextAreaElement, TextareaProps>(
  ({ className, label, helperText, error, id, disabled, rows = 3, ...props }, ref) => {
    const textareaId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="w-full space-y-1.5 text-left">
        {label && (
          <label htmlFor={textareaId} className="block text-xs font-semibold uppercase tracking-wider text-surface-700 select-none">
            {label}
          </label>
        )}

        <textarea
          ref={ref}
          id={textareaId}
          rows={rows}
          disabled={disabled}
          className={cn(
            'w-full rounded-lg border bg-white px-3.5 py-2 text-sm text-surface-900 placeholder:text-surface-400 transition-colors resize-y',
            'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500',
            'disabled:bg-surface-50 disabled:text-surface-400 disabled:cursor-not-allowed',
            error ? 'border-rose-300 focus:ring-rose-500 focus:border-rose-500' : 'border-surface-300',
            className
          )}
          {...props}
        />

        {error ? (
          <p className="text-xs font-medium text-rose-600 animate-in fade-in duration-150">{error}</p>
        ) : helperText ? (
          <p className="text-xs text-surface-500">{helperText}</p>
        ) : null}
      </div>
    );
  }
);

Textarea.displayName = 'Textarea';
