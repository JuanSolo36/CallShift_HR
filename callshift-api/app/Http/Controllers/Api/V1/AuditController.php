<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AuditLogResource;
use App\Http\Responses\ApiResponse;
use App\Models\AuditLog;
use App\Services\Audit\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AuditController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Lista los registros de auditoría filtrados y paginados bajo aislamiento de tenant.
     * GET /api/v1/audit-logs
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $perPage = (int)$request->input('per_page', 25);
        $logs = $this->auditService->queryLogs($request->all(), $request->user(), $perPage);

        return ApiResponse::success(
            AuditLogResource::collection($logs)->response()->getData(true),
            'Registros de auditoría obtenidos exitosamente.'
        );
    }

    /**
     * Muestra el detalle completo de un registro de auditoría.
     * GET /api/v1/audit-logs/{id}
     */
    public function show(int $id, Request $request): JsonResponse
    {
        $log = AuditLog::with(['user'])->findOrFail($id);
        $this->authorize('view', $log);

        return ApiResponse::success(
            new AuditLogResource($log),
            'Detalle del registro de auditoría obtenido exitosamente.'
        );
    }

    /**
     * Exporta los registros de auditoría en formato CSV.
     * GET /api/v1/audit-logs/export
     */
    public function export(Request $request): Response
    {
        $this->authorize('export', AuditLog::class);

        $csv = $this->auditService->exportLogsCsv($request->all(), $request->user());
        $filename = 'audit_logs_' . date('Ymd_His') . '.csv';

        return response($csv, 200, [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
