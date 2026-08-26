import React from 'react';
import { Card } from './Card';
import { Badge } from './Badge';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface StatCardProps {
  title: string;
  value: string | number;
  subtitle?: string;
  icon?: React.ReactNode;
  trend?: 'up' | 'down' | 'neutral';
  change?: string;
  className?: string;
  onClick?: () => void;
}

export const StatCard: React.FC<StatCardProps> = ({
  title,
  value,
  subtitle,
  icon,
  trend,
  change,
  className,
  onClick,
}) => {
  const trendIcons = {
    up: <TrendingUp className="w-3.5 h-3.5" />,
    down: <TrendingDown className="w-3.5 h-3.5" />,
    neutral: <Minus className="w-3.5 h-3.5" />,
  };

  const trendVariants = {
    up: 'success' as const,
    down: 'danger' as const,
    neutral: 'neutral' as const,
  };

  return (
    <Card
      onClick={onClick}
      hoverable={!!onClick}
      className={cn('p-5 flex flex-col justify-between space-y-4', onClick && 'cursor-pointer', className)}
    >
      <div className="flex items-center justify-between">
        <span className="text-xs font-semibold uppercase tracking-wider text-surface-500">{title}</span>
        {icon && <div className="p-2 rounded-lg bg-surface-50 text-surface-600 border border-surface-200">{icon}</div>}
      </div>

      <div>
        <div className="text-2xl font-bold tracking-tight text-surface-900 font-sans">{value}</div>
        {subtitle && <p className="text-xs text-surface-500 mt-0.5">{subtitle}</p>}
      </div>

      {change && trend && (
        <div className="flex items-center gap-2 pt-1 border-t border-surface-100">
          <Badge variant={trendVariants[trend]} size="sm" className="gap-1">
            {trendIcons[trend]}
            <span>{change}</span>
          </Badge>
          <span className="text-[11px] text-surface-400">vs. periodo anterior</span>
        </div>
      )}
    </Card>
  );
};
