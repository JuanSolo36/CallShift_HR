import React from 'react';
import { ChevronRight, Home } from 'lucide-react';
import { Link } from 'react-router-dom';
import { cn } from '@/lib/utils';

export interface BreadcrumbItem {
  label: string;
  href?: string;
  current?: boolean;
}

export interface BreadcrumbProps {
  items: BreadcrumbItem[];
  className?: string;
}

export const Breadcrumb: React.FC<BreadcrumbProps> = ({ items, className }) => {
  return (
    <nav aria-label="Breadcrumb" className={cn('flex items-center space-x-2 text-xs text-surface-500 select-none', className)}>
      <Link to="/dashboard" className="hover:text-surface-900 transition-colors flex items-center">
        <Home className="w-3.5 h-3.5" />
      </Link>

      {items.map((item, index) => (
        <React.Fragment key={index}>
          <ChevronRight className="w-3.5 h-3.5 text-surface-300 shrink-0" />
          {item.href && !item.current ? (
            <Link to={item.href} className="hover:text-surface-900 transition-colors font-medium">
              {item.label}
            </Link>
          ) : (
            <span className={cn('font-semibold text-surface-800', item.current && 'text-brand-700 font-medium')}>
              {item.label}
            </span>
          )}
        </React.Fragment>
      ))}
    </nav>
  );
};
