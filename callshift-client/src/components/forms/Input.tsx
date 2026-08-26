import React from 'react';
import { cn } from '@/lib/utils';

export interface InputProps extends React.InputHTMLAttributes<HTMLInputElement> {
  label?: string;
  helperText?: string;
  error?: string;
  leftIcon?: React.ReactNode;
  rightIcon?: React.ReactNode;
}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, label, helperText, error, leftIcon, rightIcon, id, disabled, ...props }, ref) => {
    const inputId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="w-full space-y-1.5 text-left">
        {label && (
          <label htmlFor={inputId} className="block text-xs font-semibold uppercase tracking-wider text-surface-700 select-none">
            {label}
          </label>
        )}

        <div className="relative flex items-center rounded-lg">
          {leftIcon && (
            <div className="absolute left-3 flex items-center pointer-events-none text-surface-400">
              {leftIcon}
            </div>
          )}

          <input
            ref={ref}
            id={inputId}
            disabled={disabled}
            className={cn(
              'w-full rounded-lg border bg-white px-3.5 py-2 text-sm text-surface-900 placeholder:text-surface-400 transition-colors',
              'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500',
              'disabled:bg-surface-50 disabled:text-surface-400 disabled:cursor-not-allowed',
              error ? 'border-rose-300 focus:ring-rose-500 focus:border-rose-500' : 'border-surface-300',
              leftIcon && 'pl-10',
              rightIcon && 'pr-10',
              className
            )}
            {...props}
          />

          {rightIcon && (
            <div className="absolute right-3 flex items-center text-surface-400">
              {rightIcon}
            </div>
          )}
        </div>

        {error ? (
          <p className="text-xs font-medium text-rose-600 animate-in fade-in duration-150">{error}</p>
        ) : helperText ? (
          <p className="text-xs text-surface-500">{helperText}</p>
        ) : null}
      </div>
    );
  }
);

Input.displayName = 'Input';
