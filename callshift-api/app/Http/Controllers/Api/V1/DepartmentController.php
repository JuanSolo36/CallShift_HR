<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreDepartmentRequest;
use App\Http\Requests\V1\UpdateDepartmentRequest;
use App\Http\Resources\V1\DepartmentResource;
use App\Http\Responses\ApiResponse;
use App\Services\Organization\DepartmentService;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService
    ) {}

    /**
     * Lista departamentos paginados y filtrados.
     * GET /api/v1/departments
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $filters = $request->only(['search', 'status', 'sort_by', 'sort_order']);
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->departmentService->listDepartments($filters, $perPage);

        return ApiResponse::paginated(
            DepartmentResource::collection($paginated),
            'Departamentos obtenidos exitosamente.'
        );
    }

    /**
     * Lista compacta de departamentos para selectores.
     * GET /api/v1/departments/compact
     */
    public function compact(): JsonResponse
    {
        $this->authorize('viewAny', Department::class);

        $departments = $this->departmentService->getAllDepartmentsCompact();

        return ApiResponse::success(
            $departments,
            'Lista compacta de departamentos obtenida correctamente.'
        );
    }

    /**
     * Muestra el detalle de un departamento.
     * GET /api/v1/departments/{id}
     */
    public function show(int $id): JsonResponse
    {
        $department = $this->departmentService->getDepartmentById($id);
        $this->authorize('view', $department);

        return ApiResponse::success(
            new DepartmentResource($department),
            'Detalle del departamento obtenido correctamente.'
        );
    }

    /**
     * Crea un nuevo departamento.
     * POST /api/v1/departments
     */
    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $this->authorize('create', Department::class);

        $department = $this->departmentService->createDepartment(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            new DepartmentResource($department),
            'Departamento creado exitosamente.'
        );
    }

    /**
     * Actualiza un departamento existente.
     * PUT /api/v1/departments/{id}
     */
    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $department = $this->departmentService->getDepartmentById($id);
        $this->authorize('update', $department);

        $updated = $this->departmentService->updateDepartment(
            $department,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new DepartmentResource($updated),
            'Departamento actualizado exitosamente.'
        );
    }

    /**
     * Elimina un departamento.
     * DELETE /api/v1/departments/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $department = $this->departmentService->getDepartmentById($id);
        $this->authorize('delete', $department);

        $this->departmentService->deleteDepartment($department, Auth::user());

        return ApiResponse::success(
            null,
            'Departamento eliminado exitosamente.'
        );
    }
}
