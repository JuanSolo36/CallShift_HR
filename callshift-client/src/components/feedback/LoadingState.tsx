import React from 'react';
import { Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface LoadingStateProps {
  message?: string;
  className?: string;
}

export const LoadingState: React.FC<LoadingStateProps> = ({
  message = 'Cargando información...',
  className,
}) => {
  return (
    <div className={cn('flex flex-col items-center justify-center p-12 text-center space-y-3', className)}>
      <Loader2 className="w-8 h-8 text-brand-600 animate-spin" />
      <p className="text-xs font-medium text-surface-500">{message}</p>
    </div>
  );
};
