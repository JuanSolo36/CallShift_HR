import React, { useState, useEffect } from 'react';
import { businessRuleService } from '../../../services/businessRuleService';
import { departmentService } from '../../organization/services/departmentService';
import type { BusinessRule, BusinessRuleFormData, EffectiveBusinessRules, WeekendRotationPolicy } from '../../../types/businessRule';
import type { CompactOption } from '../../../types/organization.types';
import { useAuthStore } from '../../../stores/useAuthStore';
import {
  ShieldAlert,
  Building2,
  Save,
  Trash2,
  CheckCircle2,
  AlertCircle,
  Clock,
  Calendar,
  Layers,
} from 'lucide-react';

export const BusinessRulesPage: React.FC = () => {
  const { hasPermission } = useAuthStore();
  const canManage = hasPermission('settings:manage') || hasPermission('company:update');

  const [rules, setRules] = useState<BusinessRule[]>([]);
  const [departments, setDepartments] = useState<CompactOption[]>([]);
  const [selectedDeptId, setSelectedDeptId] = useState<number | null>(null);
  const [effectiveRules, setEffectiveRules] = useState<EffectiveBusinessRules | null>(null);
  const [isSaving, setIsSaving] = useState<boolean>(false);
  const [successMsg, setSuccessMsg] = useState<string | null>(null);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  // Form State
  const [formData, setFormData] = useState<BusinessRuleFormData>({
    max_daily_hours: 10.0,
    min_daily_hours: 4.0,
    max_weekly_hours: 48.0,
    min_weekly_hours: 20.0,
    min_rest_hours_between_shifts: 12.0,
    max_consecutive_work_days: 6,
    allow_night_shifts: true,
    weekend_rotation_policy: 'FAIR_SHARE',
  });

  useEffect(() => {
    loadInitialData();
  }, []);

  useEffect(() => {
    loadEffectiveAndForm(selectedDeptId);
  }, [selectedDeptId, rules]);

  const loadInitialData = async () => {
    setErrorMsg(null);
    try {
      const [rulesData, deptsData] = await Promise.all([
        businessRuleService.list(),
        departmentService.getDepartmentsCompact(),
      ]);
      setRules(rulesData);
      setDepartments(deptsData);
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'Error al cargar las reglas de negocio.');
    }
  };

  const loadEffectiveAndForm = async (deptId: number | null) => {
    try {
      const eff = await businessRuleService.getEffective(deptId);
      setEffectiveRules(eff);

      const targetRule = rules.find((r) =>
        deptId ? r.department_id === deptId : r.department_scope_id === 0
      );

      if (targetRule) {
        setFormData({
          department_id: targetRule.department_id,
          max_daily_hours: targetRule.max_daily_hours,
          min_daily_hours: targetRule.min_daily_hours,
          max_weekly_hours: targetRule.max_weekly_hours,
          min_weekly_hours: targetRule.min_weekly_hours,
          min_rest_hours_between_shifts: targetRule.min_rest_hours_between_shifts,
          max_consecutive_work_days: targetRule.max_consecutive_work_days,
          allow_night_shifts: targetRule.allow_night_shifts,
          weekend_rotation_policy: targetRule.weekend_rotation_policy,
        });
      } else {
        // Formulario vacío para nuevo override o default
        setFormData({
          department_id: deptId,
          max_daily_hours: null,
          min_daily_hours: null,
          max_weekly_hours: null,
          min_weekly_hours: null,
          min_rest_hours_between_shifts: null,
          max_consecutive_work_days: null,
          allow_night_shifts: null,
          weekend_rotation_policy: null,
        });
      }
    } catch (err: any) {
      console.error('Error fetching effective rules', err);
    }
  };

  const handleSave = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!canManage) return;

    setIsSaving(true);
    setSuccessMsg(null);
    setErrorMsg(null);

    try {
      await businessRuleService.save({
        ...formData,
        department_id: selectedDeptId,
      });
      setSuccessMsg('Reglas de negocio guardadas correctamente.');
      const updatedRules = await businessRuleService.list();
      setRules(updatedRules);
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'Error al guardar las reglas de negocio.');
    } finally {
      setIsSaving(false);
    }
  };

  const handleDeleteOverride = async (ruleId: number) => {
    if (!canManage) return;
    if (!window.confirm('¿Está seguro de eliminar esta regla departamental? El departamento volverá a heredar la regla global de la empresa.')) {
      return;
    }

    try {
      await businessRuleService.delete(ruleId);
      setSuccessMsg('Regla departamental eliminada. Se hereda la regla global.');
      const updatedRules = await businessRuleService.list();
      setRules(updatedRules);
    } catch (err: any) {
      setErrorMsg(err?.response?.data?.message || 'Error al eliminar la regla.');
    }
  };

  const currentRule = rules.find((r) =>
    selectedDeptId ? r.department_id === selectedDeptId : r.department_scope_id === 0
  );

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-slate-900 tracking-tight flex items-center gap-2.5">
            <ShieldAlert className="w-7 h-7 text-indigo-600" />
            Reglas de Negocio & Restricciones Laborales
          </h1>
          <p className="text-sm text-slate-500 mt-1">
            Configure las restricciones legales, descansos mínimos obligatorios y políticas horarias corporativas o por departamento.
          </p>
        </div>
      </div>

      {/* Alerts */}
      {successMsg && (
        <div className="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800 text-sm flex items-center gap-2">
          <CheckCircle2 className="w-5 h-5 text-emerald-600 shrink-0" />
          <span>{successMsg}</span>
        </div>
      )}
      {errorMsg && (
        <div className="p-4 bg-rose-50 border border-rose-200 rounded-xl text-rose-800 text-sm flex items-center gap-2">
          <AlertCircle className="w-5 h-5 text-rose-600 shrink-0" />
          <span>{errorMsg}</span>
        </div>
      )}

      {/* Scope Selector Tabs */}
      <div className="bg-white p-4 rounded-xl border border-slate-200 shadow-xs flex flex-wrap items-center gap-2">
        <span className="text-xs font-bold uppercase tracking-wider text-slate-400 mr-2">
          Ámbito de Configuración:
        </span>
        <button
          onClick={() => setSelectedDeptId(null)}
          className={`px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all ${
            selectedDeptId === null
              ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-600/20'
              : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
          }`}
        >
          <Building2 className="w-4 h-4" />
          Regla Global de Empresa
          {rules.some((r) => r.department_scope_id === 0) && (
            <span className="w-2 h-2 rounded-full bg-emerald-400" />
          )}
        </button>

        {departments.map((dept) => {
          const hasOverride = rules.some((r) => r.department_id === dept.id);
          const isSelected = selectedDeptId === dept.id;
          return (
            <button
              key={dept.id}
              onClick={() => setSelectedDeptId(dept.id)}
              className={`px-3.5 py-2 rounded-lg text-xs font-semibold flex items-center gap-2 transition-all ${
                isSelected
                  ? 'bg-indigo-600 text-white shadow-xs ring-2 ring-indigo-600/20'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}
            >
              <Layers className="w-4 h-4" />
              {dept.name}
              {hasOverride && (
                <span
                  className={`px-1.5 py-0.5 rounded text-[10px] font-bold ${
                    isSelected ? 'bg-indigo-700 text-indigo-100' : 'bg-emerald-100 text-emerald-800'
                  }`}
                >
                  Override
                </span>
              )}
            </button>
          );
        })}
      </div>

      {/* Main Content Area */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Form Column */}
        <div className="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-xs p-6">
          <div className="flex items-center justify-between border-b border-slate-100 pb-4 mb-6">
            <div>
              <h2 className="text-base font-bold text-slate-900">
                {selectedDeptId === null
                  ? 'Parámetros Globales de la Empresa'
                  : `Sobrescritura para Departamento: ${departments.find((d) => d.id === selectedDeptId)?.name}`}
              </h2>
              <p className="text-xs text-slate-500 mt-0.5">
                {selectedDeptId === null
                  ? 'Aplica por defecto a todos los departamentos que no posean reglas específicas.'
                  : 'Los campos vacíos o nulos heredarán automáticamente el valor de la regla global.'}
              </p>
            </div>

            {selectedDeptId && currentRule && (
              <button
                type="button"
                onClick={() => handleDeleteOverride(currentRule.id)}
                className="px-3 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 border border-rose-200 rounded-lg flex items-center gap-1.5 transition-colors"
              >
                <Trash2 className="w-4 h-4" />
                Eliminar Override
              </button>
            )}
          </div>

          <form onSubmit={handleSave} className="space-y-6">
            {/* Daily Hours Section */}
            <div className="space-y-4">
              <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <Clock className="w-4 h-4 text-indigo-500" />
                Jornada Diaria de Trabajo
              </h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Máximo de Horas Diarias (Horas)
                  </label>
                  <input
                    type="number"
                    step="0.5"
                    min="1"
                    max="24"
                    value={formData.max_daily_hours ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        max_daily_hours: e.target.value === '' ? null : parseFloat(e.target.value),
                      })
                    }
                    placeholder={selectedDeptId ? `Heredado (${effectiveRules?.max_daily_hours}h)` : '10.0'}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                  />
                  <span className="text-[10px] text-slate-400">Límite duro de bloqueo (HARD_CONFLICT)</span>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Mínimo de Horas Diarias (Horas)
                  </label>
                  <input
                    type="number"
                    step="0.5"
                    min="0.5"
                    max="24"
                    value={formData.min_daily_hours ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        min_daily_hours: e.target.value === '' ? null : parseFloat(e.target.value),
                      })
                    }
                    placeholder={selectedDeptId ? `Heredado (${effectiveRules?.min_daily_hours}h)` : '4.0'}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                  />
                  <span className="text-[10px] text-slate-400">Aviso informativo sugerido (SOFT_WARNING)</span>
                </div>
              </div>
            </div>

            {/* Weekly Hours Section */}
            <div className="space-y-4 pt-4 border-t border-slate-100">
              <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <Calendar className="w-4 h-4 text-indigo-500" />
                Jornada Semanal & Descansos
              </h3>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Máximo de Horas Semanales (Horas)
                  </label>
                  <input
                    type="number"
                    step="0.5"
                    min="1"
                    max="168"
                    value={formData.max_weekly_hours ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        max_weekly_hours: e.target.value === '' ? null : parseFloat(e.target.value),
                      })
                    }
                    placeholder={selectedDeptId ? `Heredado (${effectiveRules?.max_weekly_hours}h)` : '48.0'}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                  />
                  <span className="text-[10px] text-slate-400">Límite legal de jornada (HARD_CONFLICT)</span>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Mínimo de Horas Semanales (Horas)
                  </label>
                  <input
                    type="number"
                    step="0.5"
                    min="1"
                    max="168"
                    value={formData.min_weekly_hours ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        min_weekly_hours: e.target.value === '' ? null : parseFloat(e.target.value),
                      })
                    }
                    placeholder={selectedDeptId ? `Heredado (${effectiveRules?.min_weekly_hours}h)` : '20.0'}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                  />
                  <span className="text-[10px] text-slate-400">Aplica solo en semanas ISO completas (SOFT_WARNING)</span>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Descanso Mínimo entre Turnos (Horas)
                  </label>
                  <input
                    type="number"
                    step="0.5"
                    min="1"
                    max="48"
                    value={formData.min_rest_hours_between_shifts ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        min_rest_hours_between_shifts: e.target.value === '' ? null : parseFloat(e.target.value),
                      })
                    }
                    placeholder={selectedDeptId ? `Heredado (${effectiveRules?.min_rest_hours_between_shifts}h)` : '12.0'}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                  />
                  <span className="text-[10px] text-slate-400">Mínimo legal obligatorio de descanso (HARD_CONFLICT)</span>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Máximo Días Consecutivos de Trabajo
                  </label>
                  <input
                    type="number"
                    min="1"
                    max="30"
                    value={formData.max_consecutive_work_days ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        max_consecutive_work_days: e.target.value === '' ? null : parseInt(e.target.value, 10),
                      })
                    }
                    placeholder={selectedDeptId ? `Heredado (${effectiveRules?.max_consecutive_work_days} días)` : '6'}
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden"
                  />
                  <span className="text-[10px] text-slate-400">Evaluado con historial anterior (HARD_CONFLICT)</span>
                </div>
              </div>
            </div>

            {/* Special Policies Section */}
            <div className="space-y-4 pt-4 border-t border-slate-100">
              <h3 className="text-xs font-bold uppercase tracking-wider text-slate-400 flex items-center gap-2">
                <ShieldAlert className="w-4 h-4 text-indigo-500" />
                Políticas Especiales & Rotación
              </h3>

              <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Política de Rotación de Fines de Semana
                  </label>
                  <select
                    value={formData.weekend_rotation_policy ?? ''}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        weekend_rotation_policy: (e.target.value === '' ? null : e.target.value) as WeekendRotationPolicy | null,
                      })
                    }
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden bg-white"
                  >
                    {selectedDeptId && <option value="">Heredar Global ({effectiveRules?.weekend_rotation_policy})</option>}
                    <option value="FAIR_SHARE">FAIR SHARE (Distribución Equitativa - Warning)</option>
                    <option value="STRICT_ROTATION">STRICT ROTATION (Máximo 1 de 2 Fines de Semana - Hard)</option>
                    <option value="NONE">NONE (Sin Restricción de Fines de Semana)</option>
                  </select>
                </div>

                <div>
                  <label className="block text-xs font-semibold text-slate-700 mb-1">
                    Permitir Turnos Nocturnos
                  </label>
                  <select
                    value={formData.allow_night_shifts === null ? '' : formData.allow_night_shifts ? 'true' : 'false'}
                    onChange={(e) =>
                      setFormData({
                        ...formData,
                        allow_night_shifts: e.target.value === '' ? null : e.target.value === 'true',
                      })
                    }
                    className="w-full text-xs p-2.5 rounded-lg border border-slate-300 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-hidden bg-white"
                  >
                    {selectedDeptId && (
                      <option value="">
                        Heredar Global ({effectiveRules?.allow_night_shifts ? 'Permitido' : 'Prohibido'})
                      </option>
                    )}
                    <option value="true">Permitido (Habilita turnos con cruce de medianoche)</option>
                    <option value="false">Prohibido (Genera HARD_CONFLICT si se asigna turno nocturno)</option>
                  </select>
                </div>
              </div>
            </div>

            {/* Save button */}
            {canManage && (
              <div className="pt-4 flex justify-end">
                <button
                  type="submit"
                  disabled={isSaving}
                  className="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md transition-all flex items-center gap-2 disabled:opacity-50"
                >
                  <Save className="w-4 h-4" />
                  {isSaving ? 'Guardando...' : 'Guardar Configuración'}
                </button>
              </div>
            )}
          </form>
        </div>

        {/* Effective Summary Column */}
        <div className="space-y-4">
          <div className="bg-slate-900 text-white rounded-2xl p-6 shadow-md border border-slate-800">
            <div className="flex items-center gap-2 text-indigo-400 text-xs font-bold uppercase tracking-wider mb-2">
              <CheckCircle2 className="w-4 h-4" />
              Reglas Efectivas en Ejecución
            </div>
            <h3 className="text-lg font-bold text-white mb-1">
              {effectiveRules?.scope === 'DEPARTMENT_OVERRIDE'
                ? 'Override Departamental Activo'
                : effectiveRules?.scope === 'GLOBAL_COMPANY'
                ? 'Regla Global de Empresa'
                : 'Valores Predeterminados del Sistema'}
            </h3>
            <p className="text-xs text-slate-400 mb-4">
              Valores resultantes aplicados por el motor de detección de conflictos para este ámbito.
            </p>

            {effectiveRules && (
              <div className="space-y-3 text-xs">
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Máx. Horas Diarias:</span>
                  <span className="font-bold text-indigo-300">{effectiveRules.max_daily_hours}h</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Mín. Horas Diarias:</span>
                  <span className="font-bold text-indigo-300">{effectiveRules.min_daily_hours}h</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Máx. Horas Semanales:</span>
                  <span className="font-bold text-indigo-300">{effectiveRules.max_weekly_hours}h</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Mín. Horas Semanales:</span>
                  <span className="font-bold text-indigo-300">{effectiveRules.min_weekly_hours}h</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Descanso Mín. entre Turnos:</span>
                  <span className="font-bold text-indigo-300">{effectiveRules.min_rest_hours_between_shifts}h</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Máx. Días Consecutivos:</span>
                  <span className="font-bold text-indigo-300">{effectiveRules.max_consecutive_work_days} días</span>
                </div>
                <div className="flex justify-between py-1.5 border-b border-slate-800">
                  <span className="text-slate-400">Turnos Nocturnos:</span>
                  <span className={`font-bold ${effectiveRules.allow_night_shifts ? 'text-emerald-400' : 'text-rose-400'}`}>
                    {effectiveRules.allow_night_shifts ? 'Habilitados' : 'Deshabilitados'}
                  </span>
                </div>
                <div className="flex justify-between py-1.5">
                  <span className="text-slate-400">Rotación Fines de Semana:</span>
                  <span className="font-bold text-amber-300">{effectiveRules.weekend_rotation_policy}</span>
                </div>
              </div>
            )}
          </div>

          <div className="bg-indigo-50 border border-indigo-200 rounded-2xl p-5 text-xs text-indigo-900 space-y-2">
            <h4 className="font-bold text-indigo-950 flex items-center gap-1.5">
              <ShieldAlert className="w-4 h-4 text-indigo-600" />
              Invariante de Integridad Física
            </h4>
            <p className="text-indigo-800/90 leading-relaxed">
              La plataforma garantiza a nivel de base de datos la unicidad estricta:{' '}
              <code className="bg-indigo-200/60 px-1 py-0.5 rounded font-mono text-[11px]">
                UNIQUE(company_id, department_scope_id)
              </code>
              . La regla global posee alcance <code className="font-mono">0</code> y cada departamento posee su id numérico correspondiente, impidiendo duplicados en entornos concurrentes.
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};
