import React from 'react';
import { ConflictValidationSummary } from '../../../types/conflict';
import { AlertCircle, AlertTriangle, CheckCircle, ShieldCheck } from 'lucide-react';

interface ConflictBadgeProps {
  summary: ConflictValidationSummary | null;
  onClick?: () => void;
  isLoading?: boolean;
}

export const ConflictBadge: React.FC<ConflictBadgeProps> = ({
  summary,
  onClick,
  isLoading = false,
}) => {
  if (isLoading) {
    return (
      <div className="inline-flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 animate-pulse border border-slate-200">
        <span className="w-2 h-2 rounded-full bg-blue-500 animate-ping" />
        Validando reglas...
      </div>
    );
  }

  if (!summary) {
    return (
      <button
        onClick={onClick}
        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition-colors"
        title="Hacer clic para validar reglas laborales"
      >
        <ShieldCheck className="w-4 h-4 text-slate-500" />
        Validar Horario
      </button>
    );
  }

  const hasHard = summary.active_hard_conflicts > 0;
  const hasSoft = summary.active_soft_warnings > 0;

  if (!hasHard && !hasSoft) {
    return (
      <button
        onClick={onClick}
        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition-colors shadow-sm"
        title="Sin conflictos activos. Horario listo para publicar."
      >
        <CheckCircle className="w-4 h-4 text-emerald-600" />
        0 Conflictos (Válido)
        {summary.resolved_exceptions > 0 && (
          <span className="ml-1 px-1.5 py-0.5 rounded-full bg-emerald-200 text-emerald-800 text-[10px]">
            {summary.resolved_exceptions} justificados
          </span>
        )}
      </button>
    );
  }

  return (
    <button
      onClick={onClick}
      className={`inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all shadow-sm ${
        hasHard
          ? 'bg-rose-50 text-rose-800 border-rose-200 hover:bg-rose-100 ring-1 ring-rose-300'
          : 'bg-amber-50 text-amber-800 border-amber-200 hover:bg-amber-100 ring-1 ring-amber-300'
      }`}
      title={
        hasHard
          ? `Bloqueo de publicación: ${summary.active_hard_conflicts} conflictos críticos.`
          : `Advertencias: ${summary.active_soft_warnings} avisos no bloqueantes.`
      }
    >
      {hasHard ? (
        <AlertCircle className="w-4 h-4 text-rose-600 animate-bounce" />
      ) : (
        <AlertTriangle className="w-4 h-4 text-amber-600" />
      )}
      <span>
        {hasHard
          ? `${summary.active_hard_conflicts} Crítico${summary.active_hard_conflicts > 1 ? 's' : ''}`
          : `${summary.active_soft_warnings} Aviso${summary.active_soft_warnings > 1 ? 's' : ''}`}
      </span>
      {hasHard && hasSoft && (
        <span className="text-slate-400">|</span>
      )}
      {hasHard && hasSoft && (
        <span className="text-amber-700 font-normal">
          +{summary.active_soft_warnings} avisos
        </span>
      )}
    </button>
  );
};
