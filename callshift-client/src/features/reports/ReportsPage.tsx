import React, { useState } from 'react';
import type { ReportType, ReportFilters } from '@/types/reports.types';
import { reportService } from '@/services/reportService';
import { Card } from '@/components/ui/Card';
import { Select } from '@/components/forms/Select';
import { Input } from '@/components/forms/Input';
import { EmptyState } from '@/components/feedback/EmptyState';

export const ReportsPage: React.FC = () => {
  const [reportType, setReportType] = useState<ReportType>('employees');
  const [filters, setFilters] = useState<ReportFilters>({});
  const [exporting, setExporting] = useState(false);

  const reportOptions = [
    { value: 'employees', label: '1. Reporte de Empleados' },
    { value: 'schedules', label: '2. Reporte de Horarios' },
    { value: 'hours', label: '3. Reporte de Horas Trabajadas' },
    { value: 'absences', label: '4. Reporte de Ausencias' },
    { value: 'modifications', label: '5. Reporte de Modificaciones' },
    { value: 'audit', label: '6. Bitácora de Auditoría' },
  ];

  const handleExport = async () => {
    try {
      setExporting(true);
      let blob: Blob;
      switch (reportType) {
        case 'employees':
          blob = await reportService.exportEmployees(filters);
          break;
        case 'schedules':
          blob = await reportService.exportSchedules(filters);
          break;
        case 'hours':
          blob = await reportService.exportHours(filters);
          break;
        case 'absences':
          blob = await reportService.exportAbsences(filters);
          break;
        case 'modifications':
          blob = await reportService.exportModifications(filters);
          break;
        case 'audit':
          blob = await reportService.exportAudit(filters);
          break;
      }

      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = `reporte_${reportType}_${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(a);
      a.click();
      window.URL.revokeObjectURL(url);
      document.body.removeChild(a);
    } catch (err) {
      console.error('Error al exportar reporte:', err);
    } finally {
      setExporting(false);
    }
  };

  return (
    <div className="space-y-6 p-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Reportes Empresariales</h1>
          <p className="text-sm text-gray-500 dark:text-gray-400">
            Consulta, filtra y exporta la información consolidada y auditada de tu organización.
          </p>
        </div>
        <button
          onClick={handleExport}
          disabled={exporting}
          className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none disabled:opacity-50"
        >
          {exporting ? 'Generando CSV...' : 'Exportar a CSV'}
        </button>
      </div>

      <Card className="p-4 space-y-4">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <Select
              label="Tipo de Reporte"
              value={reportType}
              onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setReportType(e.target.value as ReportType)}
              options={reportOptions}
            />
          </div>
          <div>
            <Input
              label="Búsqueda General"
              type="text"
              placeholder="Buscar por texto..."
              value={filters.search || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFilters({ ...filters, search: e.target.value })}
            />
          </div>
          <div>
            <Input
              label="Fecha Desde"
              type="date"
              value={filters.date_from || ''}
              onChange={(e: React.ChangeEvent<HTMLInputElement>) => setFilters({ ...filters, date_from: e.target.value })}
            />
          </div>
        </div>
      </Card>

      <Card className="p-6">
        <EmptyState
          title="Consulta de Reportes"
          description="Selecciona los filtros deseados y exporta la información en formato CSV con trazabilidad forense."
        />
      </Card>
    </div>
  );
};
