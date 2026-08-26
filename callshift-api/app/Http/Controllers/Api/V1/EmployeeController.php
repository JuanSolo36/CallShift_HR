<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreEmployeeRequest;
use App\Http\Requests\V1\UpdateEmployeeRequest;
use App\Http\Resources\V1\EmployeeResource;
use App\Http\Responses\ApiResponse;
use App\Services\Employee\EmployeeService;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class EmployeeController extends Controller
{
    public function __construct(
        protected EmployeeService $employeeService
    ) {}

    /**
     * Lista empleados paginados y filtrados.
     * GET /api/v1/employees
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $filters = $request->only([
            'search',
            'department_id',
            'position_id',
            'employment_type_id',
            'status',
            'sort_by',
            'sort_order',
        ]);
        $perPage = (int) $request->input('per_page', 15);

        $paginated = $this->employeeService->listEmployees($filters, $perPage);

        return ApiResponse::paginated(
            EmployeeResource::collection($paginated),
            'Empleados obtenidos exitosamente.'
        );
    }

    /**
     * Lista compacta de empleados para selectores y combos.
     * GET /api/v1/employees/compact
     */
    public function compact(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Employee::class);

        $excludeId = $request->has('exclude_id') ? (int) $request->input('exclude_id') : null;
        $employees = $this->employeeService->getAllEmployeesCompact($excludeId);

        return ApiResponse::success(
            $employees,
            'Lista compacta de empleados obtenida correctamente.'
        );
    }

    /**
     * Muestra el expediente completo de un empleado.
     * GET /api/v1/employees/{id}
     */
    public function show(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);
        $this->authorize('view', $employee);

        return ApiResponse::success(
            new EmployeeResource($employee),
            'Expediente del empleado obtenido correctamente.'
        );
    }

    /**
     * Registra un nuevo empleado.
     * POST /api/v1/employees
     */
    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $this->authorize('create', Employee::class);

        $employee = $this->employeeService->createEmployee(
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::created(
            new EmployeeResource($employee),
            'Empleado registrado exitosamente.'
        );
    }

    /**
     * Actualiza el expediente de un empleado.
     * PUT /api/v1/employees/{id}
     */
    public function update(UpdateEmployeeRequest $request, int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);
        $this->authorize('update', $employee);

        $updated = $this->employeeService->updateEmployee(
            $employee,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new EmployeeResource($updated),
            'Expediente del empleado actualizado exitosamente.'
        );
    }

    /**
     * Cambia el estado laboral del empleado (Activar, Suspender, Retirar).
     * PATCH /api/v1/employees/{id}/status
     */
    public function changeStatus(Request $request, int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);
        $this->authorize('update', $employee);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:ACTIVE,INACTIVE,ON_LEAVE,TERMINATED'],
            'reason' => ['nullable', 'string', 'max:500'],
        ], [
            'status.required' => 'El estado laboral es obligatorio.',
            'status.in'       => 'Estado laboral no reconocido.',
        ]);

        $updated = $this->employeeService->changeEmployeeStatus(
            $employee,
            $validated['status'],
            Auth::user(),
            $validated['reason'] ?? null
        );

        return ApiResponse::success(
            new EmployeeResource($updated),
            'Estado laboral actualizado exitosamente.'
        );
    }

    /**
     * Elimina/Desactiva un empleado del sistema.
     * DELETE /api/v1/employees/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $employee = $this->employeeService->getEmployeeById($id);
        $this->authorize('delete', $employee);

        $this->employeeService->deleteEmployee($employee, Auth::user());

        return ApiResponse::success(
            null,
            'Empleado eliminado exitosamente.'
        );
    }
}
