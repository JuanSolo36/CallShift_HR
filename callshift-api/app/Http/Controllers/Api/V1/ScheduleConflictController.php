<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\ResolveConflictRequest;
use App\Http\Resources\V1\ScheduleConflictResource;
use App\Models\ScheduleConflict;
use App\Models\ScheduleVersion;
use App\Models\WorkPeriod;
use App\Services\Conflicts\ConflictDetectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ScheduleConflictController extends Controller
{
    public function __construct(
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * POST /api/v1/schedule-versions/{versionId}/validate
     * Ejecuta el motor de validación canónico y retorna los conflictos detectados y sincronizados.
     */
    public function validateVersion(Request $request, int $versionId): JsonResponse
    {
        $version = ScheduleVersion::withoutGlobalScopes()->with(['workPeriod.company'])->findOrFail($versionId);

        $this->authorize('validate', [ScheduleConflict::class, $version]);

        $conflicts = $this->conflictService->validateVersion($version, $request->user());
        $conflicts->load(['employee.department', 'resolver']);

        $hardCount = $conflicts->where('severity.value', 'HARD_CONFLICT')->where('status.value', 'ACTIVE')->count();
        $softCount = $conflicts->where('severity.value', 'SOFT_WARNING')->where('status.value', 'ACTIVE')->count();
        $resolvedCount = $conflicts->where('is_resolved', true)->count();

        return response()->json([
            'success' => true,
            'message' => 'Validación de conflictos ejecutada exitosamente.',
            'summary' => [
                'total_conflicts'       => $conflicts->count(),
                'active_hard_conflicts' => $hardCount,
                'active_soft_warnings'  => $softCount,
                'resolved_exceptions'   => $resolvedCount,
                'can_publish'           => $hardCount === 0,
            ],
            'data'    => ScheduleConflictResource::collection($conflicts),
        ]);
    }

    /**
     * GET /api/v1/schedule-versions/{versionId}/conflicts
     * Consulta los conflictos de una versión con filtros opcionales.
     */
    public function indexByVersion(Request $request, int $versionId): JsonResponse
    {
        $version = ScheduleVersion::withoutGlobalScopes()->with(['workPeriod'])->findOrFail($versionId);

        $this->authorize('viewAny', ScheduleConflict::class);

        $workPeriod = $version->workPeriod ?? WorkPeriod::withoutGlobalScopes()->find($version->work_period_id);
        if ($workPeriod && $workPeriod->company_id !== $request->user()->company_id && !$request->user()->hasRole('SUPER_ADMIN')) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: La versión pertenece a otra empresa.',
            ], Response::HTTP_FORBIDDEN);
        }

        $query = ScheduleConflict::with(['employee.department', 'resolver'])
            ->where('schedule_version_id', $versionId);

        if ($request->filled('severity')) {
            $query->where('severity', $request->query('severity'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->query('employee_id'));
        }

        if ($request->filled('is_resolved')) {
            $query->where('is_resolved', filter_var($request->query('is_resolved'), FILTER_VALIDATE_BOOLEAN));
        }

        $conflicts = $query->orderBy('date')->get();

        return response()->json([
            'success' => true,
            'data'    => ScheduleConflictResource::collection($conflicts),
        ]);
    }

    /**
     * PATCH /api/v1/schedule-conflicts/{id}/resolve
     * Resuelve y justifica formalmente un conflicto registrando la auditoría.
     */
    public function resolve(ResolveConflictRequest $request, ScheduleConflict $conflict): JsonResponse
    {
        $conflict->load(['version.workPeriod', 'employee']);

        $this->authorize('resolve', $conflict);

        $updated = $this->conflictService->resolve(
            $conflict,
            $request->validated('reason'),
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Conflicto resuelto y justificado exitosamente.',
            'data'    => new ScheduleConflictResource($updated),
        ]);
    }
}
