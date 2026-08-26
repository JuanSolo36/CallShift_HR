import React from 'react';
import { cn } from '@/lib/utils';
import { Check } from 'lucide-react';

export interface CheckboxProps extends Omit<React.InputHTMLAttributes<HTMLInputElement>, 'type'> {
  label?: React.ReactNode;
  description?: string;
}

export const Checkbox = React.forwardRef<HTMLInputElement, CheckboxProps>(
  ({ className, label, description, id, checked, disabled, ...props }, ref) => {
    const checkboxId = id || (typeof label === 'string' ? label.toLowerCase().replace(/\s+/g, '-') : undefined);

    return (
      <div className="flex items-start space-x-3 text-left">
        <div className="relative flex items-center h-5">
          <input
            type="checkbox"
            ref={ref}
            id={checkboxId}
            checked={checked}
            disabled={disabled}
            className="peer sr-only"
            {...props}
          />
          <div
            className={cn(
              'w-4 h-4 rounded border border-surface-300 bg-white transition-all flex items-center justify-center cursor-pointer select-none',
              'peer-checked:bg-brand-600 peer-checked:border-brand-600 peer-focus:ring-2 peer-focus:ring-brand-500 peer-focus:ring-offset-1',
              'peer-disabled:bg-surface-100 peer-disabled:cursor-not-allowed peer-disabled:opacity-60',
              className
            )}
            onClick={() => {
              if (!disabled && checkboxId) {
                document.getElementById(checkboxId)?.click();
              }
            }}
          >
            <Check className="w-3 h-3 text-white stroke-[3] opacity-0 peer-checked:opacity-100 transition-opacity" />
          </div>
        </div>

        {(label || description) && (
          <div className="text-xs">
            {label && (
              <label
                htmlFor={checkboxId}
                className={cn('font-medium text-surface-900 cursor-pointer select-none', disabled && 'cursor-not-allowed text-surface-400')}
              >
                {label}
              </label>
            )}
            {description && <p className="text-surface-500 mt-0.5">{description}</p>}
          </div>
        )}
      </div>
    );
  }
);

Checkbox.displayName = 'Checkbox';
