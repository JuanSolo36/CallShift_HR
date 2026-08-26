<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreWorkPeriodRequest;
use App\Http\Requests\V1\UpdateWorkPeriodRequest;
use App\Http\Requests\V1\ChangeWorkPeriodStatusRequest;
use App\Http\Resources\V1\WorkPeriodResource;
use App\Http\Responses\ApiResponse;
use App\Services\WorkPeriods\WorkPeriodService;
use App\Models\WorkPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class WorkPeriodController extends Controller
{
    public function __construct(
        protected WorkPeriodService $workPeriodService
    ) {}

    /**
     * Listado paginado y filtrado de periodos laborales.
     * GET /api/v1/work-periods
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkPeriod::class);

        $filters = $request->only(['search', 'status', 'department_id', 'start_date', 'end_date', 'sort_by', 'sort_order']);
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->workPeriodService->listWorkPeriods($filters, $perPage);

        return ApiResponse::paginated(
            WorkPeriodResource::collection($paginated),
            'Periodos laborales obtenidos exitosamente.'
        );
    }

    /**
     * Listado compacto para selectores de mallas y planificadores.
     * GET /api/v1/work-periods/compact
     */
    public function compact(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkPeriod::class);

        $filters = $request->only(['department_id', 'status']);
        $periods = $this->workPeriodService->getAllWorkPeriodsCompact($filters);

        return ApiResponse::success(
            WorkPeriodResource::collection($periods),
            'Lista compacta de periodos obtenida correctamente.'
        );
    }

    /**
     * Detalle de un periodo laboral.
     * GET /api/v1/work-periods/{id}
     */
    public function show(int $id): JsonResponse
    {
        $period = $this->workPeriodService->getWorkPeriodById($id);
        $this->authorize('view', $period);

        return ApiResponse::success(
            new WorkPeriodResource($period),
            'Detalle del periodo obtenido correctamente.'
        );
    }

    /**
     * Crea un nuevo periodo laboral e inicializa su versión 1 en borrador.
     * POST /api/v1/work-periods
     */
    public function store(StoreWorkPeriodRequest $request): JsonResponse
    {
        $this->authorize('create', WorkPeriod::class);

        $period = $this->workPeriodService->createWorkPeriod(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            new WorkPeriodResource($period),
            'Periodo laboral creado exitosamente.'
        );
    }

    /**
     * Actualiza las fechas o parámetros de un periodo laboral.
     * PUT /api/v1/work-periods/{id}
     */
    public function update(UpdateWorkPeriodRequest $request, int $id): JsonResponse
    {
        $period = $this->workPeriodService->getWorkPeriodById($id);
        $this->authorize('update', $period);

        $updated = $this->workPeriodService->updateWorkPeriod(
            $period,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new WorkPeriodResource($updated),
            'Periodo laboral actualizado exitosamente.'
        );
    }

    /**
     * Transición de estado en el ciclo de vida del periodo.
     * PATCH /api/v1/work-periods/{id}/status
     */
    public function changeStatus(ChangeWorkPeriodStatusRequest $request, int $id): JsonResponse
    {
        $period = $this->workPeriodService->getWorkPeriodById($id);
        $this->authorize('changeStatus', $period);

        $validated = $request->validated();

        $updated = $this->workPeriodService->changeWorkPeriodStatus(
            $period,
            $validated['status'],
            $validated['reason'] ?? null,
            isset($validated['lock_version']) ? (int)$validated['lock_version'] : null,
            Auth::user()
        );

        return ApiResponse::success(
            new WorkPeriodResource($updated),
            'Estado del periodo actualizado exitosamente.'
        );
    }

    /**
     * Elimina un periodo laboral no publicado.
     * DELETE /api/v1/work-periods/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $period = $this->workPeriodService->getWorkPeriodById($id);
        $this->authorize('delete', $period);

        $this->workPeriodService->deleteWorkPeriod($period, Auth::user());

        return ApiResponse::success(
            null,
            'Periodo laboral eliminado exitosamente.'
        );
    }
}
