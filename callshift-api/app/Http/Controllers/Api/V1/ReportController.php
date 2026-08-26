<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Responses\ApiResponse;
use App\Http\Resources\V1\EmployeeResource;
use App\Http\Resources\V1\AuditLogResource;
use App\Http\Resources\V1\ScheduleModificationResource;
use App\Http\Resources\V1\ScheduleAssignmentResource;
use App\Http\Resources\V1\AbsenceResource;
use App\Services\Reports\ReportService;
use App\Policies\ReportPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ReportController extends Controller
{
    public function __construct(
        protected ReportService $reportService,
        protected ReportPolicy $policy
    ) {}

    protected function checkViewPermission(Request $request, string $type): void
    {
        $response = $this->policy->view($request->user(), $type);
        if ($response->denied()) {
            abort(Response::HTTP_FORBIDDEN, $response->message());
        }
    }

    protected function checkExportPermission(Request $request, string $type): void
    {
        $response = $this->policy->export($request->user(), $type);
        if ($response->denied()) {
            abort(Response::HTTP_FORBIDDEN, $response->message());
        }
    }

    // 1. EMPLEADOS
    public function employees(Request $request): JsonResponse
    {
        $this->checkViewPermission($request, 'employees');
        $perPage = (int)$request->input('per_page', 25);
        $paginator = $this->reportService->getEmployeesReport($request->all(), $request->user(), $perPage);

        return ApiResponse::success(
            EmployeeResource::collection($paginator)->response()->getData(true),
            'Reporte de empleados obtenido exitosamente.'
        );
    }

    public function exportEmployees(Request $request): Response
    {
        $this->checkExportPermission($request, 'employees');
        $csv = $this->reportService->exportEmployeesCsv($request->all(), $request->user());
        $filename = 'reporte_empleados_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // 2. HORARIOS
    public function schedules(Request $request): JsonResponse
    {
        $this->checkViewPermission($request, 'schedules');
        $perPage = (int)$request->input('per_page', 25);
        $paginator = $this->reportService->getSchedulesReport($request->all(), $request->user(), $perPage);

        return ApiResponse::success(
            ScheduleAssignmentResource::collection($paginator)->response()->getData(true),
            'Reporte de horarios obtenido exitosamente.'
        );
    }

    public function exportSchedules(Request $request): Response
    {
        $this->checkExportPermission($request, 'schedules');
        $csv = $this->reportService->exportSchedulesCsv($request->all(), $request->user());
        $filename = 'reporte_horarios_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // 3. HORAS
    public function hours(Request $request): JsonResponse
    {
        $this->checkViewPermission($request, 'hours');
        $reportData = $this->reportService->getHoursReport($request->all(), $request->user());

        return ApiResponse::success(
            $reportData,
            'Reporte de horas trabajadas obtenido exitosamente.'
        );
    }

    public function exportHours(Request $request): Response
    {
        $this->checkExportPermission($request, 'hours');
        $csv = $this->reportService->exportHoursCsv($request->all(), $request->user());
        $filename = 'reporte_horas_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // 4. AUSENCIAS
    public function absences(Request $request): JsonResponse
    {
        $this->checkViewPermission($request, 'absences');
        $perPage = (int)$request->input('per_page', 25);
        $paginator = $this->reportService->getAbsencesReport($request->all(), $request->user(), $perPage);

        return ApiResponse::success(
            AbsenceResource::collection($paginator)->response()->getData(true),
            'Reporte de ausencias obtenido exitosamente.'
        );
    }

    public function exportAbsences(Request $request): Response
    {
        $this->checkExportPermission($request, 'absences');
        $csv = $this->reportService->exportAbsencesCsv($request->all(), $request->user());
        $filename = 'reporte_ausencias_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // 5. MODIFICACIONES
    public function modifications(Request $request): JsonResponse
    {
        $this->checkViewPermission($request, 'modifications');
        $perPage = (int)$request->input('per_page', 25);
        $paginator = $this->reportService->getModificationsReport($request->all(), $request->user(), $perPage);

        return ApiResponse::success(
            ScheduleModificationResource::collection($paginator)->response()->getData(true),
            'Reporte de modificaciones obtenido exitosamente.'
        );
    }

    public function exportModifications(Request $request): Response
    {
        $this->checkExportPermission($request, 'modifications');
        $csv = $this->reportService->exportModificationsCsv($request->all(), $request->user());
        $filename = 'reporte_modificaciones_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    // 6. AUDITORÍA
    public function audit(Request $request): JsonResponse
    {
        $this->checkViewPermission($request, 'audit');
        $perPage = (int)$request->input('per_page', 25);
        $paginator = $this->reportService->getAuditReport($request->all(), $request->user(), $perPage);

        return ApiResponse::success(
            AuditLogResource::collection($paginator)->response()->getData(true),
            'Reporte de auditoría obtenido exitosamente.'
        );
    }

    public function exportAudit(Request $request): Response
    {
        $this->checkExportPermission($request, 'audit');
        $csv = $this->reportService->exportAuditCsv($request->all(), $request->user());
        $filename = 'reporte_auditoria_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
