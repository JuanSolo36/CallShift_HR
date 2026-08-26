import React from 'react';
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-react';

export interface SelectOption {
  value: string | number;
  label: string;
  disabled?: boolean;
}

export interface SelectProps extends React.SelectHTMLAttributes<HTMLSelectElement> {
  label?: string;
  helperText?: string;
  error?: string;
  options: SelectOption[];
  placeholder?: string;
}

export const Select = React.forwardRef<HTMLSelectElement, SelectProps>(
  ({ className, label, helperText, error, options, placeholder, id, disabled, ...props }, ref) => {
    const selectId = id || (label ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="w-full space-y-1.5 text-left">
        {label && (
          <label htmlFor={selectId} className="block text-xs font-semibold uppercase tracking-wider text-surface-700 select-none">
            {label}
          </label>
        )}

        <div className="relative flex items-center">
          <select
            ref={ref}
            id={selectId}
            disabled={disabled}
            className={cn(
              'w-full appearance-none rounded-lg border bg-white px-3.5 py-2 pr-10 text-sm text-surface-900 transition-colors',
              'focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-brand-500',
              'disabled:bg-surface-50 disabled:text-surface-400 disabled:cursor-not-allowed',
              error ? 'border-rose-300 focus:ring-rose-500 focus:border-rose-500' : 'border-surface-300',
              className
            )}
            {...props}
          >
            {placeholder && (
              <option value="" disabled>
                {placeholder}
              </option>
            )}
            {options.map((option) => (
              <option key={option.value} value={option.value} disabled={option.disabled}>
                {option.label}
              </option>
            ))}
          </select>

          <div className="absolute right-3 flex items-center pointer-events-none text-surface-400">
            <ChevronDown className="w-4 h-4" />
          </div>
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

Select.displayName = 'Select';
