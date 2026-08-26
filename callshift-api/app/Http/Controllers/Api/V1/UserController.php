<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreUserRequest;
use App\Http\Requests\V1\UpdateUserRequest;
use App\Http\Requests\V1\ChangeUserStatusRequest;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\Users\UserService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Listado paginado de usuarios del tenant.
     * GET /api/v1/users
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $filters = $request->only(['search', 'status', 'role_id', 'department_id', 'sort_by', 'sort_order']);
        $perPage = min(max((int) $request->input('per_page', 15), 5), 100);

        $paginator = $this->userService->listUsers($filters, $perPage);

        return ApiResponse::paginated(
            UserResource::collection($paginator),
            'Usuarios obtenidos correctamente.'
        );
    }

    /**
     * Consulta individual de usuario.
     * GET /api/v1/users/{id}
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('view', $user);

        return ApiResponse::success(
            new UserResource($user),
            'Detalle de usuario obtenido correctamente.'
        );
    }

    /**
     * Creación de usuario en el tenant.
     * POST /api/v1/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $user = $this->userService->createUser($request->validated(), Auth::user());

        return ApiResponse::created(
            new UserResource($user),
            'Usuario creado exitosamente.'
        );
    }

    /**
     * Actualización de datos de usuario.
     * PUT /api/v1/users/{id}
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('update', $user);

        $updatedUser = $this->userService->updateUser($user, $request->validated(), Auth::user());

        return ApiResponse::success(
            new UserResource($updatedUser),
            'Usuario actualizado exitosamente.'
        );
    }

    /**
     * Cambio de estado de usuario (ACTIVE, INACTIVE, SUSPENDED).
     * PATCH /api/v1/users/{id}/status
     */
    public function changeStatus(ChangeUserStatusRequest $request, int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('changeStatus', $user);

        $updatedUser = $this->userService->changeUserStatus(
            $user,
            $request->input('status'),
            Auth::user(),
            $request->input('reason')
        );

        return ApiResponse::success(
            new UserResource($updatedUser),
            'Estado de usuario actualizado correctamente.'
        );
    }

    /**
     * Eliminación lógica (soft delete) de usuario.
     * DELETE /api/v1/users/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);
        $this->authorize('delete', $user);

        $this->userService->deleteUser($user, Auth::user());

        return ApiResponse::success(
            null,
            'Usuario eliminado correctamente.'
        );
    }
}
