import React, { useState } from 'react';
import { PageHeader } from '@/components/layout/PageHeader';
import { StatCard } from '@/components/ui/StatCard';
import { Card, CardHeader, CardTitle, CardContent } from '@/components/ui/Card';
import { Button } from '@/components/ui/Button';
import { Badge } from '@/components/ui/Badge';
import { Table, TableHeader, TableBody, TableRow, TableHead, TableCell } from '@/components/ui/Table';
import { Modal } from '@/components/ui/Modal';
import { ConfirmDialog } from '@/components/feedback/ConfirmDialog';
import { Alert } from '@/components/feedback/Alert';
import { useAuthStore } from '@/stores/useAuthStore';
import { useUIStore } from '@/stores/useUIStore';
import {
  Users,
  UserCheck,
  CalendarDays,
  PlaneTakeoff,
  Plus,
  ArrowRight,
  Clock,
  Sparkles,
} from 'lucide-react';

export const DashboardPage: React.FC = () => {
  const { user } = useAuthStore();
  const { addToast } = useUIStore();
  const [demoModalOpen, setDemoModalOpen] = useState(false);
  const [confirmOpen, setConfirmOpen] = useState(false);

  return (
    <div className="space-y-6 text-left select-none">
      {/* Page Header */}
      <PageHeader
        title={`Bienvenido, ${user?.employee?.first_name || user?.username || 'Administrador'}`}
        description="Panel general de control de Recursos Humanos y planificación de jornadas activas."
        actions={
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              leftIcon={<Clock className="w-3.5 h-3.5" />}
              onClick={() => setDemoModalOpen(true)}
            >
              Ver Novedades
            </Button>
            <Button
              variant="primary"
              size="sm"
              leftIcon={<Plus className="w-3.5 h-3.5" />}
              onClick={() => {
                addToast({
                  type: 'info',
                  title: 'Planificación de Periodo',
                  message: 'El módulo completo de planificación se habilitará en la FASE 12.',
                });
              }}
            >
              Crear Horario
            </Button>
          </div>
        }
      />

      {/* System Status Banner */}
      <Alert variant="info" title="FASE 5 — Frontend Base Activo">
        Sistema de diseño empresarial, layout responsivo y componentes UI listos para operar conectados a la API REST v1.
      </Alert>

      {/* 4 Main Minimalist KPI Cards */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <StatCard
          title="Total Empleados"
          value="1,248"
          subtitle="Distribuidos en 6 departamentos"
          icon={<Users className="w-5 h-5 text-brand-600" />}
          trend="up"
          change="+12 este mes"
        />

        <StatCard
          title="Empleados Activos"
          value="1,215"
          subtitle="97.3% de fuerza laboral operativa"
          icon={<UserCheck className="w-5 h-5 text-emerald-600" />}
          trend="neutral"
          change="Estable"
        />

        <StatCard
          title="Horarios Publicados"
          value="42"
          subtitle="Periodos laborales vigentes"
          icon={<CalendarDays className="w-5 h-5 text-sky-600" />}
          trend="up"
          change="100% al día"
        />

        <StatCard
          title="Ausencias / Novedades"
          value="14"
          subtitle="6 pendientes de revisión"
          icon={<PlaneTakeoff className="w-5 h-5 text-amber-600" />}
          trend="down"
          change="-4 vs semana anterior"
        />
      </div>

      {/* Main Grid: Activity Table & Quick Shortcuts */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Left Column: Published Work Periods Table */}
        <div className="lg:col-span-2 space-y-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between pb-3">
              <div>
                <CardTitle>Planificaciones y Periodos Recientes</CardTitle>
                <p className="text-xs text-surface-500 mt-1">Estado de publicación por departamento</p>
              </div>
              <Badge variant="brand" size="sm">
                Semana 34
              </Badge>
            </CardHeader>
            <CardContent className="p-0">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Departamento</TableHead>
                    <TableHead>Periodo</TableHead>
                    <TableHead>Versión</TableHead>
                    <TableHead>Estado</TableHead>
                    <TableHead className="text-right">Acción</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  <TableRow>
                    <TableCell className="font-semibold text-surface-900">Operaciones y Servicios</TableCell>
                    <TableCell className="text-xs">17/08/2026 — 23/08/2026</TableCell>
                    <TableCell>
                      <Badge variant="neutral" size="sm">
                        v2 Oficial
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant="success" size="sm" dot>
                        Publicado
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="sm" className="h-7 text-xs">
                        Ver Malla
                      </Button>
                    </TableCell>
                  </TableRow>

                  <TableRow>
                    <TableCell className="font-semibold text-surface-900">Atención al Cliente & Contact Center</TableCell>
                    <TableCell className="text-xs">17/08/2026 — 23/08/2026</TableCell>
                    <TableCell>
                      <Badge variant="neutral" size="sm">
                        v1 Oficial
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant="success" size="sm" dot>
                        Publicado
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="sm" className="h-7 text-xs">
                        Ver Malla
                      </Button>
                    </TableCell>
                  </TableRow>

                  <TableRow>
                    <TableCell className="font-semibold text-surface-900">Tecnología e Innovación</TableCell>
                    <TableCell className="text-xs">24/08/2026 — 30/08/2026</TableCell>
                    <TableCell>
                      <Badge variant="neutral" size="sm">
                        v1 Borrador
                      </Badge>
                    </TableCell>
                    <TableCell>
                      <Badge variant="warning" size="sm" dot>
                        En Revisión
                      </Badge>
                    </TableCell>
                    <TableCell className="text-right">
                      <Button variant="ghost" size="sm" className="h-7 text-xs">
                        Editar
                      </Button>
                    </TableCell>
                  </TableRow>
                </TableBody>
              </Table>
            </CardContent>
          </Card>
        </div>

        {/* Right Column: Quick Actions and System Diagnostics */}
        <div className="space-y-4">
          <Card>
            <CardHeader className="pb-3">
              <CardTitle>Acciones Rápidas</CardTitle>
            </CardHeader>
            <CardContent className="space-y-2">
              <Button
                variant="secondary"
                size="sm"
                className="w-full justify-between text-xs"
                rightIcon={<ArrowRight className="w-3.5 h-3.5" />}
                onClick={() => setDemoModalOpen(true)}
              >
                <span>Registrar Ausencia Médica</span>
              </Button>

              <Button
                variant="secondary"
                size="sm"
                className="w-full justify-between text-xs"
                rightIcon={<ArrowRight className="w-3.5 h-3.5" />}
                onClick={() => setConfirmOpen(true)}
              >
                <span>Probar Cuadro de Confirmación</span>
              </Button>

              <Button
                variant="secondary"
                size="sm"
                className="w-full justify-between text-xs"
                rightIcon={<Sparkles className="w-3.5 h-3.5 text-brand-500" />}
                onClick={() => {
                  addToast({
                    type: 'success',
                    title: 'Motor de Horarios (CSP)',
                    message: 'Motor generador preparado para integración en FASE 16.',
                  });
                }}
              >
                <span>Generador Automático</span>
              </Button>
            </CardContent>
          </Card>

          <Card className="bg-surface-50 border-dashed">
            <CardContent className="p-4 space-y-2">
              <div className="text-xs font-semibold text-surface-900">Estado de Empresa y Sesión</div>
              <div className="text-xs text-surface-600 space-y-1">
                <div>
                  <span className="text-surface-400">Empresa:</span>{' '}
                  <strong className="text-surface-800">{user?.company?.name || 'CallShift Enterprise'}</strong>
                </div>
                <div>
                  <span className="text-surface-400">Rol:</span>{' '}
                  <strong className="text-surface-800">{user?.role?.name || 'Super Administrador'}</strong>
                </div>
                <div>
                  <span className="text-surface-400">Zona Horaria:</span>{' '}
                  <span className="font-mono text-surface-700">{user?.company?.timezone || 'America/Bogota'}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      {/* Demo Modal */}
      <Modal
        isOpen={demoModalOpen}
        onClose={() => setDemoModalOpen(false)}
        title="Detalle de Novedades y Solicitudes"
        description="Ventana modal reutilizable del sistema de diseño CallShift HR."
        footer={
          <Button variant="primary" size="sm" onClick={() => setDemoModalOpen(false)}>
            Entendido
          </Button>
        }
      >
        <div className="space-y-3 text-xs text-surface-600">
          <p>
            Este diálogo modal demuestra la integración de componentes accesibles con soporte para tecla{' '}
            <kbd className="px-1.5 py-0.5 rounded bg-surface-100 border text-[11px] font-mono">Escape</kbd>, bloqueo
            de scroll y capas de desenfoque sutil.
          </p>
        </div>
      </Modal>

      {/* Confirm Dialog */}
      <ConfirmDialog
        isOpen={confirmOpen}
        onClose={() => setConfirmOpen(false)}
        onConfirm={() => {
          setConfirmOpen(false);
          addToast({
            type: 'success',
            title: 'Acción Confirmada',
            message: 'El diálogo de confirmación ejecutó la acción con éxito.',
          });
        }}
        title="¿Confirmar acción crítica?"
        message="Esta acción demuestra un cuadro de diálogo modal para prevenir acciones destructivas involuntarias."
      />
    </div>
  );
};
