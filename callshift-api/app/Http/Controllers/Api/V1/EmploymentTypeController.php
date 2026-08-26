<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreEmploymentTypeRequest;
use App\Http\Requests\V1\UpdateEmploymentTypeRequest;
use App\Http\Resources\V1\EmploymentTypeResource;
use App\Http\Responses\ApiResponse;
use App\Services\Organization\EmploymentTypeService;
use App\Models\EmploymentType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmploymentTypeController extends Controller
{
    public function __construct(
        protected EmploymentTypeService $employmentTypeService
    ) {}

    /**
     * Lista tipos de empleo/contrato paginados y filtrados.
     * GET /api/v1/employment-types
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', EmploymentType::class);

        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->employmentTypeService->listEmploymentTypes($filters, $perPage);

        return ApiResponse::paginated(
            EmploymentTypeResource::collection($paginated),
            'Tipos de contrato obtenidos exitosamente.'
        );
    }

    /**
     * Lista compacta de tipos de empleo para selectores y combos.
     * GET /api/v1/employment-types/compact
     */
    public function compact(): JsonResponse
    {
        $this->authorize('viewAny', EmploymentType::class);

        $types = $this->employmentTypeService->getAllEmploymentTypesCompact();

        return ApiResponse::success(
            $types,
            'Lista compacta de tipos de contrato obtenida correctamente.'
        );
    }

    /**
     * Muestra el detalle de un tipo de contrato.
     * GET /api/v1/employment-types/{id}
     */
    public function show(int $id): JsonResponse
    {
        $employmentType = $this->employmentTypeService->getEmploymentTypeById($id);
        $this->authorize('view', $employmentType);

        return ApiResponse::success(
            new EmploymentTypeResource($employmentType),
            'Detalle del tipo de contrato obtenido correctamente.'
        );
    }

    /**
     * Registra un nuevo tipo de contrato.
     * POST /api/v1/employment-types
     */
    public function store(StoreEmploymentTypeRequest $request): JsonResponse
    {
        $this->authorize('create', EmploymentType::class);

        $employmentType = $this->employmentTypeService->createEmploymentType(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            new EmploymentTypeResource($employmentType),
            'Tipo de contrato creado exitosamente.'
        );
    }

    /**
     * Actualiza un tipo de contrato existente.
     * PUT /api/v1/employment-types/{id}
     */
    public function update(UpdateEmploymentTypeRequest $request, int $id): JsonResponse
    {
        $employmentType = $this->employmentTypeService->getEmploymentTypeById($id);
        $this->authorize('update', $employmentType);

        $updated = $this->employmentTypeService->updateEmploymentType(
            $employmentType,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new EmploymentTypeResource($updated),
            'Tipo de contrato actualizado exitosamente.'
        );
    }

    /**
     * Elimina un tipo de contrato.
     * DELETE /api/v1/employment-types/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $employmentType = $this->employmentTypeService->getEmploymentTypeById($id);
        $this->authorize('delete', $employmentType);

        $this->employmentTypeService->deleteEmploymentType($employmentType, Auth::user());

        return ApiResponse::success(
            null,
            'Tipo de contrato eliminado exitosamente.'
        );
    }
}
