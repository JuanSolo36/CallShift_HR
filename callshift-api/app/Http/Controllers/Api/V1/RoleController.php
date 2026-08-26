<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\RoleResource;
use App\Http\Resources\V1\PermissionResource;
use App\Http\Responses\ApiResponse;
use App\Services\Roles\RoleService;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService
    ) {}

    /**
     * Listado de roles disponibles.
     * GET /api/v1/roles
     */
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        $roles = $this->roleService->listRoles(Auth::user());

        return ApiResponse::success(
            RoleResource::collection($roles),
            'Roles obtenidos correctamente.'
        );
    }

    /**
     * Detalle de rol con sus permisos asociados.
     * GET /api/v1/roles/{id}
     */
    public function show(int $id): JsonResponse
    {
        $role = $this->roleService->getRoleById($id, Auth::user());
        $this->authorize('view', $role);

        return ApiResponse::success(
            new RoleResource($role),
            'Detalle de rol obtenido correctamente.'
        );
    }

    /**
     * Listado de todos los permisos del sistema.
     * GET /api/v1/permissions
     */
    public function permissions(): JsonResponse
    {
        $permissions = $this->roleService->listPermissions();

        return ApiResponse::success(
            PermissionResource::collection($permissions),
            'Permisos del sistema obtenidos correctamente.'
        );
    }
}
