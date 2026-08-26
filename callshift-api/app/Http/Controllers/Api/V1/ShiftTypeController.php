<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreShiftTypeRequest;
use App\Http\Requests\V1\UpdateShiftTypeRequest;
use App\Http\Resources\V1\ShiftTypeResource;
use App\Http\Responses\ApiResponse;
use App\Services\Shifts\ShiftTypeService;
use App\Models\ShiftType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ShiftTypeController extends Controller
{
    public function __construct(
        protected ShiftTypeService $shiftTypeService
    ) {}

    /**
     * Lista tipos de turno paginados y filtrados.
     * GET /api/v1/shift-types
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ShiftType::class);

        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->shiftTypeService->listShiftTypes($filters, $perPage);

        return ApiResponse::paginated(
            ShiftTypeResource::collection($paginated),
            'Tipos de turno obtenidos exitosamente.'
        );
    }

    /**
     * Lista compacta de turnos para selectores y mallas.
     * GET /api/v1/shift-types/compact
     */
    public function compact(): JsonResponse
    {
        $this->authorize('viewAny', ShiftType::class);

        $types = $this->shiftTypeService->getAllShiftTypesCompact();

        return ApiResponse::success(
            $types,
            'Lista compacta de tipos de turno obtenida correctamente.'
        );
    }

    /**
     * Muestra el detalle de un tipo de turno.
     * GET /api/v1/shift-types/{id}
     */
    public function show(int $id): JsonResponse
    {
        $shiftType = $this->shiftTypeService->getShiftTypeById($id);
        $this->authorize('view', $shiftType);

        return ApiResponse::success(
            new ShiftTypeResource($shiftType),
            'Detalle del tipo de turno obtenido correctamente.'
        );
    }

    /**
     * Registra un nuevo tipo de turno.
     * POST /api/v1/shift-types
     */
    public function store(StoreShiftTypeRequest $request): JsonResponse
    {
        $this->authorize('create', ShiftType::class);

        $shiftType = $this->shiftTypeService->createShiftType(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            new ShiftTypeResource($shiftType),
            'Tipo de turno creado exitosamente.'
        );
    }

    /**
     * Actualiza un tipo de turno existente.
     * PUT /api/v1/shift-types/{id}
     */
    public function update(UpdateShiftTypeRequest $request, int $id): JsonResponse
    {
        $shiftType = $this->shiftTypeService->getShiftTypeById($id);
        $this->authorize('update', $shiftType);

        $updated = $this->shiftTypeService->updateShiftType(
            $shiftType,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new ShiftTypeResource($updated),
            'Tipo de turno actualizado exitosamente.'
        );
    }

    /**
     * Elimina un tipo de turno.
     * DELETE /api/v1/shift-types/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $shiftType = $this->shiftTypeService->getShiftTypeById($id);
        $this->authorize('delete', $shiftType);

        $this->shiftTypeService->deleteShiftType($shiftType, Auth::user());

        return ApiResponse::success(
            null,
            'Tipo de turno eliminado exitosamente.'
        );
    }
}
