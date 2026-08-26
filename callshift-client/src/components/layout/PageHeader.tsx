import React from 'react';
import { Breadcrumb, type BreadcrumbItem } from '../navigation/Breadcrumb';
import { cn } from '@/lib/utils';

export interface PageHeaderProps {
  title: string;
  description?: string;
  breadcrumbs?: BreadcrumbItem[];
  actions?: React.ReactNode;
  className?: string;
}

export const PageHeader: React.FC<PageHeaderProps> = ({
  title,
  description,
  breadcrumbs,
  actions,
  className,
}) => {
  return (
    <div className={cn('flex flex-col gap-3 pb-6 border-b border-surface-200/80 mb-6 text-left', className)}>
      {breadcrumbs && breadcrumbs.length > 0 && <Breadcrumb items={breadcrumbs} />}

      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-xl font-bold text-surface-900 tracking-tight">{title}</h1>
          {description && <p className="text-xs text-surface-500 mt-1">{description}</p>}
        </div>

        {actions && <div className="flex items-center gap-2.5 shrink-0">{actions}</div>}
      </div>
    </div>
  );
};
