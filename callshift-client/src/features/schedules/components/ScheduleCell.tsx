import React from 'react';
import type { ScheduleAssignmentItem, ScheduleGridDay } from '@/types/schedule.types';

interface ScheduleCellProps {
  day: ScheduleGridDay;
  assignment?: ScheduleAssignmentItem | null;
  isEditable: boolean;
  onClick: () => void;
}

export const ScheduleCell: React.FC<ScheduleCellProps> = ({
  day,
  assignment,
  isEditable,
  onClick,
}) => {
  const getCellContent = () => {
    if (!assignment) {
      return (
        <div className="w-full h-full min-h-[38px] flex items-center justify-center text-surface-300 group-hover:text-brand-600 transition-colors">
          {isEditable && (
            <span className="opacity-0 group-hover:opacity-100 text-xs font-semibold select-none">
              +
            </span>
          )}
        </div>
      );
    }

    // Si es un día no laboral especial
    if (assignment.day_type !== 'WORK') {
      const typeConfig: Record<string, { label: string; bg: string; text: string }> = {
        REST: { label: 'DESC', bg: 'bg-emerald-50 border-emerald-200', text: 'text-emerald-700 font-semibold' },
        OFF: { label: 'LIBRE', bg: 'bg-surface-100 border-surface-200', text: 'text-surface-600' },
        HOLIDAY: { label: 'FEST', bg: 'bg-amber-50 border-amber-200', text: 'text-amber-700 font-semibold' },
        PERMISSION: { label: 'PERM', bg: 'bg-indigo-50 border-indigo-200', text: 'text-indigo-700 font-semibold' },
        ABSENCE: { label: 'AUS', bg: 'bg-rose-50 border-rose-200', text: 'text-rose-700 font-bold' },
      };

      const cfg = typeConfig[assignment.day_type] || {
        label: assignment.day_type,
        bg: 'bg-surface-100 border-surface-200',
        text: 'text-surface-700',
      };

      return (
        <div
          className={`w-full h-full min-h-[38px] rounded border px-1.5 py-1 flex flex-col items-center justify-center text-center select-none ${cfg.bg}`}
        >
          <span className={`text-[10px] tracking-tight ${cfg.text}`}>{cfg.label}</span>
        </div>
      );
    }

    // Turno asignado
    const shift = assignment.shift_type;
    const colorHex = shift?.color_hex || '#3B82F6';
    const shiftCode = shift?.code || assignment.start_time || 'T';

    return (
      <div
        className="w-full h-full min-h-[38px] rounded border px-1.5 py-0.5 flex flex-col justify-center select-none shadow-2xs transition-transform group-hover:scale-[1.02]"
        style={{
          backgroundColor: `${colorHex}15`,
          borderColor: `${colorHex}50`,
          borderLeftWidth: '3.5px',
          borderLeftColor: colorHex,
        }}
      >
        <div className="flex items-center justify-between gap-1 leading-tight">
          <span className="font-bold text-[11px] text-surface-900 truncate">
            {shiftCode}
          </span>
          {assignment.total_hours > 0 && (
            <span className="text-[9px] font-mono text-surface-600 font-medium">
              {assignment.total_hours}h
            </span>
          )}
        </div>
        {assignment.start_time && assignment.end_time && (
          <div className="text-[9px] font-mono text-surface-500 truncate leading-none mt-0.5">
            {assignment.start_time.substring(0, 5)}-{assignment.end_time.substring(0, 5)}
          </div>
        )}
      </div>
    );
  };

  return (
    <td
      onClick={isEditable ? onClick : undefined}
      className={`p-1 border border-surface-200/80 transition-all ${
        day.is_weekend ? 'bg-surface-50/70' : 'bg-white'
      } ${
        isEditable
          ? 'cursor-pointer hover:bg-brand-50/30 group'
          : 'cursor-default'
      }`}
    >
      {getCellContent()}
    </td>
  );
};
