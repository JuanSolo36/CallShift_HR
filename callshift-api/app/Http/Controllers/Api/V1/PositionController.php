<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StorePositionRequest;
use App\Http\Requests\V1\UpdatePositionRequest;
use App\Http\Resources\V1\PositionResource;
use App\Http\Responses\ApiResponse;
use App\Services\Organization\PositionService;
use App\Models\Position;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class PositionController extends Controller
{
    public function __construct(
        protected PositionService $positionService
    ) {}

    /**
     * Lista cargos paginados y filtrados.
     * GET /api/v1/positions
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $filters = $request->only(['search', 'department_id', 'status', 'sort_by', 'sort_order']);
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->positionService->listPositions($filters, $perPage);

        return ApiResponse::paginated(
            PositionResource::collection($paginated),
            'Cargos obtenidos exitosamente.'
        );
    }

    /**
     * Lista compacta de cargos para selectores.
     * GET /api/v1/positions/compact
     */
    public function compact(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Position::class);

        $departmentId = $request->has('department_id') ? (int) $request->input('department_id') : null;
        $positions = $this->positionService->getAllPositionsCompact($departmentId);

        return ApiResponse::success(
            $positions,
            'Lista compacta de cargos obtenida correctamente.'
        );
    }

    /**
     * Muestra el detalle de un cargo.
     * GET /api/v1/positions/{id}
     */
    public function show(int $id): JsonResponse
    {
        $position = $this->positionService->getPositionById($id);
        $this->authorize('view', $position);

        return ApiResponse::success(
            new PositionResource($position),
            'Detalle del cargo obtenido correctamente.'
        );
    }

    /**
     * Crea un nuevo cargo.
     * POST /api/v1/positions
     */
    public function store(StorePositionRequest $request): JsonResponse
    {
        $this->authorize('create', Position::class);

        $position = $this->positionService->createPosition(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            new PositionResource($position),
            'Cargo creado exitosamente.'
        );
    }

    /**
     * Actualiza un cargo existente.
     * PUT /api/v1/positions/{id}
     */
    public function update(UpdatePositionRequest $request, int $id): JsonResponse
    {
        $position = $this->positionService->getPositionById($id);
        $this->authorize('update', $position);

        $updated = $this->positionService->updatePosition(
            $position,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new PositionResource($updated),
            'Cargo actualizado exitosamente.'
        );
    }

    /**
     * Elimina un cargo.
     * DELETE /api/v1/positions/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $position = $this->positionService->getPositionById($id);
        $this->authorize('delete', $position);

        $this->positionService->deletePosition($position, Auth::user());

        return ApiResponse::success(
            null,
            'Cargo eliminado exitosamente.'
        );
    }
}
