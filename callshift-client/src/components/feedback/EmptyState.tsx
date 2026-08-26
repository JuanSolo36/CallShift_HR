import React from 'react';
import { Inbox } from 'lucide-react';
import { cn } from '@/lib/utils';
import { Button } from '../ui/Button';

export interface EmptyStateProps {
  icon?: React.ReactNode;
  title: string;
  description?: string;
  actionText?: string;
  onAction?: () => void;
  className?: string;
}

export const EmptyState: React.FC<EmptyStateProps> = ({
  icon,
  title,
  description,
  actionText,
  onAction,
  className,
}) => {
  return (
    <div className={cn('flex flex-col items-center justify-center p-12 text-center rounded-xl border border-dashed border-surface-300 bg-surface-50/50', className)}>
      <div className="p-3 rounded-2xl bg-white text-surface-400 border border-surface-200 shadow-xs mb-3">
        {icon || <Inbox className="w-6 h-6 stroke-[1.5]" />}
      </div>
      <h4 className="text-sm font-semibold text-surface-900">{title}</h4>
      {description && <p className="text-xs text-surface-500 max-w-sm mt-1 mb-4">{description}</p>}
      {actionText && onAction && (
        <Button size="sm" variant="primary" onClick={onAction}>
          {actionText}
        </Button>
      )}
    </div>
  );
};
