import React, { useMemo } from 'react';
import { ScheduleCell } from './ScheduleCell';
import { Badge } from '@/components/ui/Badge';
import { User, Clock } from 'lucide-react';
import type {
  ScheduleGridData,
  ScheduleGridEmployee,
  ScheduleAssignmentItem,
  ScheduleGridDay,
} from '@/types/schedule.types';

interface ScheduleGridProps {
  gridData: ScheduleGridData;
  isEditable: boolean;
  onCellClick: (employee: ScheduleGridEmployee, day: ScheduleGridDay, currentAssignment: ScheduleAssignmentItem | null) => void;
}

export const ScheduleGrid: React.FC<ScheduleGridProps> = ({
  gridData,
  isEditable,
  onCellClick,
}) => {
  const { employees, days, assignments } = gridData;

  // Mapa rápido de asignaciones: key = `${employee_id}_${date}`
  const assignmentMap = useMemo(() => {
    const map = new Map<string, ScheduleAssignmentItem>();
    assignments.forEach((a) => {
      map.set(`${a.employee_id}_${a.date}`, a);
    });
    return map;
  }, [assignments]);

  // Horas totales por empleado en este periodo
  const employeeHoursMap = useMemo(() => {
    const map = new Map<number, number>();
    assignments.forEach((a) => {
      if (a.day_type === 'WORK') {
        const current = map.get(a.employee_id) || 0;
        map.set(a.employee_id, current + (a.total_hours || 0));
      }
    });
    return map;
  }, [assignments]);

  // Conteo de colaboradores asignados por día
  const dailyStaffCount = useMemo(() => {
    const countMap = new Map<string, number>();
    days.forEach((d) => {
      let workingCount = 0;
      employees.forEach((e) => {
        const assign = assignmentMap.get(`${e.id}_${d.date}`);
        if (assign && assign.day_type === 'WORK') {
          workingCount++;
        }
      });
      countMap.set(d.date, workingCount);
    });
    return countMap;
  }, [days, employees, assignmentMap]);

  return (
    <div className="w-full overflow-x-auto border border-surface-200 rounded-lg shadow-2xs bg-white">
      <table className="w-full border-collapse text-left min-w-[750px]">
        <thead>
          <tr className="bg-surface-100/80 border-b border-surface-200">
            {/* Columna Colaborador (Sticky Left) */}
            <th className="p-3 text-xs font-bold text-surface-700 w-64 min-w-[220px] sticky left-0 bg-surface-100 z-10 border-r border-surface-200">
              <div className="flex items-center gap-1.5">
                <User className="w-4 h-4 text-surface-500" />
                <span>Colaborador / Puesto</span>
              </div>
            </th>

            {/* Columnas de Días */}
            {days.map((day) => (
              <th
                key={day.date}
                className={`p-2 text-center text-xs font-semibold border-r border-surface-200/80 min-w-[90px] ${
                  day.is_weekend ? 'bg-surface-200/50 text-surface-700' : 'text-surface-900'
                }`}
              >
                <div className="text-[11px] uppercase tracking-wider text-surface-500 font-bold">
                  {day.day_name}
                </div>
                <div className="text-sm font-extrabold text-surface-900 font-mono">
                  {day.day_number}
                </div>
              </th>
            ))}

            {/* Columna Total Horas */}
            <th className="p-2 text-center text-xs font-bold text-surface-700 w-24 min-w-[90px] bg-surface-100">
              <div className="flex items-center justify-center gap-1">
                <Clock className="w-3.5 h-3.5 text-surface-400" />
                <span>Horas</span>
              </div>
            </th>
          </tr>
        </thead>

        <tbody>
          {employees.map((emp) => {
            const totalHours = employeeHoursMap.get(emp.id) || 0;

            return (
              <tr key={emp.id} className="hover:bg-surface-50/50 transition-colors">
                {/* Info Colaborador */}
                <td className="p-2.5 border-b border-r border-surface-200 sticky left-0 bg-white z-10">
                  <div className="font-semibold text-xs text-surface-900 leading-tight truncate">
                    {emp.full_name}
                  </div>
                  <div className="text-[11px] text-surface-500 flex items-center gap-1 mt-0.5 truncate">
                    <span className="font-mono text-[10px] bg-surface-100 px-1 py-0.2 rounded text-surface-600">
                      {emp.employee_code}
                    </span>
                    <span className="truncate">{emp.position || emp.department}</span>
                  </div>
                </td>

                {/* Celdas diarias */}
                {days.map((day) => {
                  const assignment = assignmentMap.get(`${emp.id}_${day.date}`) || null;
                  return (
                    <ScheduleCell
                      key={day.date}
                      day={day}
                      assignment={assignment}
                      isEditable={isEditable}
                      onClick={() => onCellClick(emp, day, assignment)}
                    />
                  );
                })}

                {/* Total de Horas */}
                <td className="p-2 text-center border-b border-surface-200 bg-surface-50/40">
                  <Badge
                    variant={totalHours > 48 ? 'warning' : totalHours > 0 ? 'brand' : 'neutral'}
                    size="sm"
                    className="font-mono text-[11px]"
                  >
                    {totalHours}h
                  </Badge>
                </td>
              </tr>
            );
          })}
        </tbody>

        {/* Fila de Totales de Cobertura */}
        <tfoot>
          <tr className="bg-surface-100/90 border-t-2 border-surface-300 font-semibold text-xs">
            <td className="p-2.5 text-surface-700 sticky left-0 bg-surface-100 z-10 border-r border-surface-200">
              Personal en Turno
            </td>
            {days.map((day) => {
              const count = dailyStaffCount.get(day.date) || 0;
              return (
                <td
                  key={day.date}
                  className="p-2 text-center font-mono text-xs border-r border-surface-200 text-surface-800"
                >
                  <span className={count === 0 ? 'text-rose-500 font-bold' : 'text-brand-700 font-bold'}>
                    {count}
                  </span>
                </td>
              );
            })}
            <td className="p-2 text-center text-surface-500 text-[10px]">
              {employees.length} activos
            </td>
          </tr>
        </tfoot>
      </table>
    </div>
  );
};
