<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UpsertScheduleAssignmentRequest;
use App\Http\Requests\V1\DeleteScheduleAssignmentRequest;
use App\Http\Resources\V1\ScheduleGridResource;
use App\Http\Resources\V1\ScheduleAssignmentResource;
use App\Http\Responses\ApiResponse;
use App\Services\Schedule\ScheduleEditorService;
use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Models\ScheduleAssignment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ScheduleEditorController extends Controller
{
    public function __construct(
        protected ScheduleEditorService $editorService
    ) {}

    /**
     * Carga el contexto completo de la malla para un periodo laboral.
     * GET /api/v1/work-periods/{workPeriodId}/schedule
     */
    public function getScheduleByPeriod(Request $request, int $workPeriodId): JsonResponse
    {
        $versionId = $request->input('version_id') ? (int) $request->input('version_id') : null;
        $gridData = $this->editorService->getGridData($workPeriodId, $versionId, Auth::user());

        $this->authorize('view', $gridData['version']);

        return ApiResponse::success(
            new ScheduleGridResource($gridData),
            'Malla de horarios obtenida exitosamente.'
        );
    }

    /**
     * Carga el contexto de la malla a partir del ID de la versión.
     * GET /api/v1/schedule-versions/{versionId}/grid
     */
    public function getGridByVersion(int $versionId): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($versionId);
        $this->authorize('view', $version);

        $gridData = $this->editorService->getGridData($version->work_period_id, $versionId, Auth::user());

        return ApiResponse::success(
            new ScheduleGridResource($gridData),
            'Malla de horarios obtenida exitosamente.'
        );
    }

    /**
     * Lista las asignaciones individuales de una versión de horario.
     * GET /api/v1/schedule-versions/{versionId}/assignments
     */
    public function listAssignments(int $versionId): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($versionId);
        $this->authorize('view', $version);

        $assignments = ScheduleAssignment::where('schedule_version_id', $version->id)
            ->with(['shiftType', 'employee'])
            ->get();

        return ApiResponse::success(
            ScheduleAssignmentResource::collection($assignments),
            'Asignaciones de la versión obtenidas exitosamente.'
        );
    }

    /**
     * Crea o actualiza una asignación en una celda (Upsert).
     * POST /api/v1/schedule-versions/{versionId}/assignments
     */
    public function upsertAssignment(UpsertScheduleAssignmentRequest $request, int $versionId): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($versionId);
        $this->authorize('update', $version);

        $result = $this->editorService->upsertAssignment(
            $version,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success([
            'assignment'   => new ScheduleAssignmentResource($result['assignment']),
            'lock_version' => $result['lock_version'],
        ], 'Asignación guardada exitosamente.');
    }

    /**
     * Actualiza una celda existente.
     * PUT /api/v1/schedule-versions/{versionId}/assignments/{assignmentId}
     */
    public function updateAssignment(UpsertScheduleAssignmentRequest $request, int $versionId, int $assignmentId): JsonResponse
    {
        return $this->upsertAssignment($request, $versionId);
    }

    /**
     * Elimina / libera la asignación de una celda.
     * DELETE /api/v1/schedule-versions/{versionId}/assignments/{assignmentId}
     */
    public function destroyAssignment(DeleteScheduleAssignmentRequest $request, int $versionId, int $assignmentId): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($versionId);
        $this->authorize('update', $version);

        $lockVersion = (int) $request->input('lock_version');

        $result = $this->editorService->deleteAssignment(
            $version,
            $assignmentId,
            $lockVersion,
            Auth::user()
        );

        return ApiResponse::success([
            'lock_version' => $result['lock_version'],
        ], 'Asignación eliminada exitosamente.');
    }

    /**
     * Rutas helper / alias a nivel de WorkPeriod
     */
    public function upsertByWorkPeriod(UpsertScheduleAssignmentRequest $request, int $workPeriodId): JsonResponse
    {
        $workPeriod = WorkPeriod::where('company_id', Auth::user()->company_id)->findOrFail($workPeriodId);
        $versionId = $workPeriod->current_version_id;
        if (!$versionId) {
            $version = ScheduleVersion::where('work_period_id', $workPeriod->id)->firstOrFail();
            $versionId = $version->id;
        }
        return $this->upsertAssignment($request, $versionId);
    }

    public function destroyByWorkPeriod(DeleteScheduleAssignmentRequest $request, int $workPeriodId, int $assignmentId): JsonResponse
    {
        $workPeriod = WorkPeriod::where('company_id', Auth::user()->company_id)->findOrFail($workPeriodId);
        $versionId = $workPeriod->current_version_id;
        if (!$versionId) {
            $version = ScheduleVersion::where('work_period_id', $workPeriod->id)->firstOrFail();
            $versionId = $version->id;
        }
        return $this->destroyAssignment($request, $versionId, $assignmentId);
    }
}
