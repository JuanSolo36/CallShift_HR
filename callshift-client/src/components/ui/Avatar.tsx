import React from 'react';
import { cn } from '@/lib/utils';

export interface AvatarProps extends React.HTMLAttributes<HTMLDivElement> {
  name: string;
  src?: string | null;
  size?: 'sm' | 'md' | 'lg' | 'xl';
  status?: 'online' | 'busy' | 'offline';
}

export const Avatar: React.FC<AvatarProps> = ({
  name,
  src,
  size = 'md',
  status,
  className,
  ...props
}) => {
  const getInitials = (n: string): string => {
    const parts = n.trim().split(/\s+/);
    if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
    return (parts[0][0] + parts[1][0]).toUpperCase();
  };

  const sizes = {
    sm: 'w-7 h-7 text-[10px]',
    md: 'w-9 h-9 text-xs',
    lg: 'w-11 h-11 text-sm',
    xl: 'w-14 h-14 text-base font-semibold',
  };

  const statusColors = {
    online: 'bg-emerald-500 ring-white',
    busy: 'bg-amber-500 ring-white',
    offline: 'bg-surface-400 ring-white',
  };

  return (
    <div className="relative inline-block select-none" {...props}>
      <div
        className={cn(
          'rounded-full flex items-center justify-center font-medium bg-brand-100 text-brand-700 border border-brand-200 overflow-hidden',
          sizes[size],
          className
        )}
      >
        {src ? (
          <img src={src} alt={name} className="w-full h-full object-cover" />
        ) : (
          <span>{getInitials(name)}</span>
        )}
      </div>

      {status && (
        <span
          className={cn(
            'absolute bottom-0 right-0 w-2.5 h-2.5 rounded-full ring-2',
            statusColors[status]
          )}
        />
      )}
    </div>
  );
};
