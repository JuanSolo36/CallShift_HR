import React, { useState } from 'react';
import type { ScheduleConflict, ConflictValidationSummary } from '../../../types/conflict';
import { conflictService } from '../../../services/conflictService';
import {
  AlertCircle,
  AlertTriangle,
  CheckCircle,
  X,
  RefreshCw,
  User,
  Calendar,
  Clock,
  ShieldCheck,
  FileCheck2,
  Info,
} from 'lucide-react';

interface ConflictPanelProps {
  isOpen: boolean;
  onClose: () => void;
  versionId?: number;
  conflicts: ScheduleConflict[];
  summary: ConflictValidationSummary | null;
  onRevalidate: () => Promise<void>;
  onConflictResolved?: () => void;
  canResolve?: boolean;
}

export const ConflictPanel: React.FC<ConflictPanelProps> = ({
  isOpen,
  onClose,
  conflicts,
  summary,
  onRevalidate,
  onConflictResolved,
  canResolve = true,
}) => {
  const [filterSeverity, setFilterSeverity] = useState<string>('ALL');
  const [filterStatus, setFilterStatus] = useState<string>('ACTIVE');
  const [selectedConflict, setSelectedConflict] = useState<ScheduleConflict | null>(null);
  const [resolutionReason, setResolutionReason] = useState<string>('');
  const [isResolving, setIsResolving] = useState<boolean>(false);
  const [isValidating, setIsValidating] = useState<boolean>(false);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  if (!isOpen) return null;

  const filteredConflicts = conflicts.filter((c) => {
    if (filterSeverity !== 'ALL' && c.severity !== filterSeverity) return false;
    if (filterStatus === 'ACTIVE' && (c.status !== 'ACTIVE' || c.is_resolved)) return false;
    if (filterStatus === 'RESOLVED' && !c.is_resolved) return false;
    if (filterStatus === 'AUTO_CLEARED' && c.status !== 'AUTO_CLEARED') return false;
    return true;
  });

  const handleRevalidate = async () => {
    setIsValidating(true);
    setErrorMsg(null);
    try {
      await onRevalidate();
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'Error al revalidar conflictos.');
    } finally {
      setIsValidating(false);
    }
  };

  const handleOpenResolveModal = (conflict: ScheduleConflict) => {
    setSelectedConflict(conflict);
    setResolutionReason('');
    setErrorMsg(null);
  };

  const handleConfirmResolve = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!selectedConflict) return;
    if (resolutionReason.trim().length < 5) {
      setErrorMsg('La justificación de la excepción debe contener al menos 5 caracteres.');
      return;
    }

    setIsResolving(true);
    setErrorMsg(null);
    try {
      await conflictService.resolveConflict(selectedConflict.id, resolutionReason.trim());
      setSelectedConflict(null);
      setResolutionReason('');
      if (onConflictResolved) {
        onConflictResolved();
      }
      await onRevalidate();
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'No se pudo registrar la excepción.');
    } finally {
      setIsResolving(false);
    }
  };

  return (
    <div className="fixed inset-0 z-50 overflow-hidden">
      {/* Overlay */}
      <div
        className="absolute inset-0 bg-slate-900/40 backdrop-blur-xs transition-opacity"
        onClick={onClose}
      />

      <div className="fixed inset-y-0 right-0 max-w-full flex pl-10">
        <div className="w-screen max-w-2xl bg-white shadow-2xl flex flex-col">
          {/* Header */}
          <div className="p-6 bg-slate-900 text-white flex items-center justify-between border-b border-slate-800">
            <div className="flex items-center gap-3">
              <div className="p-2 bg-indigo-600/30 rounded-lg text-indigo-400 border border-indigo-500/30">
                <ShieldCheck className="w-6 h-6" />
              </div>
              <div>
                <h2 className="text-lg font-bold">Motor de Validación & Conflictos</h2>
                <p className="text-xs text-slate-400">
                  Reglas laborales, descansos y restricciones operativas
                </p>
              </div>
            </div>
            <div className="flex items-center gap-2">
              <button
                onClick={handleRevalidate}
                disabled={isValidating}
                className="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition-colors disabled:opacity-50"
                title="Revalidar todas las reglas"
              >
                <RefreshCw className={`w-4 h-4 ${isValidating ? 'animate-spin' : ''}`} />
              </button>
              <button
                onClick={onClose}
                className="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-400 hover:text-white transition-colors"
              >
                <X className="w-5 h-5" />
              </button>
            </div>
          </div>

          {/* Metrics Bar */}
          {summary && (
            <div className="grid grid-cols-4 gap-2 p-4 bg-slate-50 border-b border-slate-200 text-center text-xs">
              <div className="p-2 rounded-lg bg-rose-50 border border-rose-200">
                <div className="font-bold text-rose-800 text-base">
                  {summary.active_hard_conflicts}
                </div>
                <div className="text-rose-600 font-medium">Críticos (Bloqueo)</div>
              </div>
              <div className="p-2 rounded-lg bg-amber-50 border border-amber-200">
                <div className="font-bold text-amber-800 text-base">
                  {summary.active_soft_warnings}
                </div>
                <div className="text-amber-600 font-medium">Avisos Suaves</div>
              </div>
              <div className="p-2 rounded-lg bg-emerald-50 border border-emerald-200">
                <div className="font-bold text-emerald-800 text-base">
                  {summary.resolved_exceptions}
                </div>
                <div className="text-emerald-600 font-medium">Justificados</div>
              </div>
              <div className="p-2 rounded-lg bg-blue-50 border border-blue-200">
                <div className="font-bold text-blue-800 text-base">
                  {summary.total_conflicts}
                </div>
                <div className="text-blue-600 font-medium">Total Histórico</div>
              </div>
            </div>
          )}

          {/* Filters */}
          <div className="px-6 py-3 bg-white border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div className="flex items-center gap-2">
              <span className="text-slate-500 font-semibold">Severidad:</span>
              <button
                onClick={() => setFilterSeverity('ALL')}
                className={`px-2.5 py-1 rounded-md transition-colors ${
                  filterSeverity === 'ALL'
                    ? 'bg-slate-900 text-white font-medium'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                Todas
              </button>
              <button
                onClick={() => setFilterSeverity('HARD_CONFLICT')}
                className={`px-2.5 py-1 rounded-md transition-colors ${
                  filterSeverity === 'HARD_CONFLICT'
                    ? 'bg-rose-600 text-white font-medium'
                    : 'bg-rose-50 text-rose-700 hover:bg-rose-100'
                }`}
              >
                Críticos
              </button>
              <button
                onClick={() => setFilterSeverity('SOFT_WARNING')}
                className={`px-2.5 py-1 rounded-md transition-colors ${
                  filterSeverity === 'SOFT_WARNING'
                    ? 'bg-amber-600 text-white font-medium'
                    : 'bg-amber-50 text-amber-700 hover:bg-amber-100'
                }`}
              >
                Avisos
              </button>
            </div>

            <div className="flex items-center gap-2">
              <span className="text-slate-500 font-semibold">Estado:</span>
              <button
                onClick={() => setFilterStatus('ACTIVE')}
                className={`px-2.5 py-1 rounded-md transition-colors ${
                  filterStatus === 'ACTIVE'
                    ? 'bg-slate-900 text-white font-medium'
                    : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                }`}
              >
                Activos
              </button>
              <button
                onClick={() => setFilterStatus('RESOLVED')}
                className={`px-2.5 py-1 rounded-md transition-colors ${
                  filterStatus === 'RESOLVED'
                    ? 'bg-emerald-600 text-white font-medium'
                    : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'
                }`}
              >
                Justificados
              </button>
              <button
                onClick={() => setFilterStatus('AUTO_CLEARED')}
                className={`px-2.5 py-1 rounded-md transition-colors ${
                  filterStatus === 'AUTO_CLEARED'
                    ? 'bg-slate-600 text-white font-medium'
                    : 'bg-slate-100 text-slate-500 hover:bg-slate-200'
                }`}
              >
                Auto-Resueltos
              </button>
            </div>
          </div>

          {/* List of Conflicts */}
          <div className="flex-1 overflow-y-auto p-6 space-y-4">
            {errorMsg && (
              <div className="p-3 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-xs flex items-center gap-2">
                <AlertCircle className="w-4 h-4 shrink-0 text-rose-600" />
                <span>{errorMsg}</span>
              </div>
            )}

            {filteredConflicts.length === 0 ? (
              <div className="text-center py-16 px-4">
                <CheckCircle className="w-12 h-12 mx-auto text-emerald-400 mb-3" />
                <h3 className="text-sm font-bold text-slate-800">
                  No hay conflictos para este filtro
                </h3>
                <p className="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                  La planificación cumple con las reglas laborales y restricciones configuradas.
                </p>
              </div>
            ) : (
              filteredConflicts.map((c) => {
                const isHard = c.severity === 'HARD_CONFLICT';
                return (
                  <div
                    key={c.id}
                    className={`p-4 rounded-xl border transition-all ${
                      c.is_resolved
                        ? 'bg-emerald-50/50 border-emerald-200'
                        : c.status === 'AUTO_CLEARED'
                        ? 'bg-slate-50 border-slate-200 opacity-60'
                        : isHard
                        ? 'bg-rose-50/60 border-rose-200 ring-1 ring-rose-300/50'
                        : 'bg-amber-50/60 border-amber-200 ring-1 ring-amber-300/50'
                    }`}
                  >
                    <div className="flex items-start justify-between gap-3">
                      <div className="flex items-center gap-2">
                        {c.is_resolved ? (
                          <FileCheck2 className="w-5 h-5 text-emerald-600 shrink-0" />
                        ) : isHard ? (
                          <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
                        ) : (
                          <AlertTriangle className="w-5 h-5 text-amber-600 shrink-0" />
                        )}
                        <div>
                          <span
                            className={`inline-block px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ${
                              c.is_resolved
                                ? 'bg-emerald-200 text-emerald-900'
                                : isHard
                                ? 'bg-rose-200 text-rose-900'
                                : 'bg-amber-200 text-amber-900'
                            }`}
                          >
                            {c.rule_violated.replace(/_/g, ' ')}
                          </span>
                          {c.is_resolved && (
                            <span className="ml-2 inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-100 text-emerald-700">
                              Excepción Aprobada
                            </span>
                          )}
                          {c.status === 'AUTO_CLEARED' && (
                            <span className="ml-2 inline-block px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-200 text-slate-700">
                              Auto-Corregido
                            </span>
                          )}
                        </div>
                      </div>

                      {canResolve && !c.is_resolved && c.status === 'ACTIVE' && (
                        <button
                          onClick={() => handleOpenResolveModal(c)}
                          className="px-3 py-1 bg-white hover:bg-slate-100 text-slate-700 border border-slate-300 text-xs font-semibold rounded-lg shadow-xs transition-colors"
                        >
                          Justificar Excepción
                        </button>
                      )}
                    </div>

                    <p className="text-sm font-semibold text-slate-800 mt-2">{c.description}</p>

                    {c.suggested_resolution && (
                      <div className="mt-2 text-xs bg-white/70 p-2.5 rounded-lg border border-slate-200/80 text-slate-600 flex items-start gap-2">
                        <Info className="w-4 h-4 text-blue-500 shrink-0 mt-0.5" />
                        <div>
                          <span className="font-semibold text-slate-700">Acción sugerida: </span>
                          {c.suggested_resolution}
                        </div>
                      </div>
                    )}

                    {/* Metadata tags */}
                    <div className="mt-3 pt-3 border-t border-slate-200/60 flex flex-wrap items-center gap-4 text-xs text-slate-500">
                      {c.employee && (
                        <div className="flex items-center gap-1.5 font-medium text-slate-700">
                          <User className="w-3.5 h-3.5 text-slate-400" />
                          <span>
                            {c.employee.first_name} {c.employee.last_name} ({c.employee.employee_code})
                          </span>
                        </div>
                      )}
                      <div className="flex items-center gap-1.5">
                        <Calendar className="w-3.5 h-3.5 text-slate-400" />
                        <span>{c.date}</span>
                      </div>
                      {c.start_datetime && c.end_datetime && (
                        <div className="flex items-center gap-1.5">
                          <Clock className="w-3.5 h-3.5 text-slate-400" />
                          <span>
                            {new Date(c.start_datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })} -{' '}
                            {new Date(c.end_datetime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                          </span>
                        </div>
                      )}
                    </div>

                    {/* Resolution details */}
                    {c.is_resolved && (
                      <div className="mt-3 p-2.5 rounded-lg bg-emerald-100/70 border border-emerald-300/60 text-xs text-emerald-900">
                        <div className="font-semibold flex items-center justify-between">
                          <span>Justificación de excepción:</span>
                          <span className="text-[10px] text-emerald-700">
                            {c.resolved_at ? new Date(c.resolved_at).toLocaleString() : ''}
                          </span>
                        </div>
                        <p className="mt-1 italic">"{c.resolution_reason}"</p>
                        {c.resolver && (
                          <div className="mt-1 text-[10px] text-emerald-800">
                            Autorizado por: {c.resolver.name} ({c.resolver.email})
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                );
              })
            )}
          </div>
        </div>
      </div>

      {/* Modal Justificar Excepción */}
      {selectedConflict && (
        <div className="fixed inset-0 z-60 overflow-y-auto flex items-center justify-center p-4">
          <div className="fixed inset-0 bg-slate-900/60 backdrop-blur-xs" onClick={() => setSelectedConflict(null)} />

          <div className="relative bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 className="text-lg font-bold text-slate-900">Justificar Excepción Laboral</h3>
            <p className="text-xs text-slate-500 mt-1">
              Esta acción queda registrada en el log de auditoría forense con su firma de usuario y fecha.
            </p>

            <div className="mt-4 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs space-y-1">
              <div>
                <span className="font-semibold text-slate-700">Regla: </span>
                <span className="text-slate-900">{selectedConflict.rule_violated}</span>
              </div>
              <div>
                <span className="font-semibold text-slate-700">Descripción: </span>
                <span className="text-slate-900">{selectedConflict.description}</span>
              </div>
            </div>

            <form onSubmit={handleConfirmResolve} className="mt-4 space-y-4">
              <div>
                <label className="block text-xs font-semibold text-slate-700 mb-1">
                  Motivo o justificación de la excepción <span className="text-rose-500">*</span>
                </label>
                <textarea
                  rows={3}
                  value={resolutionReason}
                  onChange={(e) => setResolutionReason(e.target.value)}
                  placeholder="Ej: Autorizado por contingencia operativa con acuerdo firmado del colaborador."
                  required
                  className="w-full text-xs p-3 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                />
              </div>

              {errorMsg && (
                <div className="p-2.5 bg-rose-50 border border-rose-200 rounded-lg text-rose-700 text-xs">
                  {errorMsg}
                </div>
              )}

              <div className="flex items-center justify-end gap-2 pt-2">
                <button
                  type="button"
                  onClick={() => setSelectedConflict(null)}
                  className="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-lg transition-colors"
                >
                  Cancelar
                </button>
                <button
                  type="submit"
                  disabled={isResolving || resolutionReason.trim().length < 5}
                  className="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold rounded-lg shadow-sm transition-colors disabled:opacity-50"
                >
                  {isResolving ? 'Registrando...' : 'Confirmar Justificación'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
};
