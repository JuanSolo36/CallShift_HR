import React from 'react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Button } from '../ui/Button';
import { cn } from '@/lib/utils';

export interface PaginationProps {
  currentPage: number;
  totalPages: number;
  onPageChange: (page: number) => void;
  totalItems?: number;
  fromItem?: number;
  toItem?: number;
  className?: string;
}

export const Pagination: React.FC<PaginationProps> = ({
  currentPage,
  totalPages,
  onPageChange,
  totalItems,
  fromItem,
  toItem,
  className,
}) => {
  return (
    <div className={cn('flex flex-col sm:flex-row items-center justify-between gap-4 py-3 text-xs text-surface-500', className)}>
      {totalItems !== undefined && (
        <div>
          Mostrando <span className="font-semibold text-surface-900">{fromItem ?? (currentPage - 1) * 10 + 1}</span> a{' '}
          <span className="font-semibold text-surface-900">{toItem ?? Math.min(currentPage * 10, totalItems)}</span> de{' '}
          <span className="font-semibold text-surface-900">{totalItems}</span> registros
        </div>
      )}

      <div className="flex items-center space-x-1">
        <Button
          variant="outline"
          size="sm"
          disabled={currentPage <= 1}
          onClick={() => onPageChange(currentPage - 1)}
          className="h-8 px-2"
          aria-label="Página anterior"
        >
          <ChevronLeft className="w-4 h-4" />
        </Button>

        <span className="px-3 py-1 text-xs font-medium text-surface-700 bg-surface-50 rounded-lg border border-surface-200">
          Página {currentPage} de {totalPages || 1}
        </span>

        <Button
          variant="outline"
          size="sm"
          disabled={currentPage >= totalPages}
          onClick={() => onPageChange(currentPage + 1)}
          className="h-8 px-2"
          aria-label="Página siguiente"
        >
          <ChevronRight className="w-4 h-4" />
        </Button>
      </div>
    </div>
  );
};
