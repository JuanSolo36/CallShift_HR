<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\LoginRequest;
use App\Http\Requests\V1\ChangePasswordRequest;
use App\Http\Resources\V1\AuthResource;
use App\Http\Resources\V1\UserResource;
use App\Http\Responses\ApiResponse;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Inicia sesión y emite un token Sanctum.
     * POST /api/v1/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $request->ensureIsNotRateLimited();

        try {
            $result = $this->authService->login(
                $request->input('login'),
                $request->input('password'),
                $request->input('device_name'),
                $request->ip(),
                $request->userAgent()
            );

            $request->clearRateLimiter();

            return ApiResponse::success(
                new AuthResource($result['user'], $result['token']),
                'Inicio de sesión exitoso.'
            );
        } catch (ValidationException $e) {
            $request->hitRateLimiter();
            throw $e;
        }
    }

    /**
     * Cierra la sesión activa revocando el token de Sanctum.
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout(
            $request->user(),
            $request->ip(),
            $request->userAgent()
        );

        return ApiResponse::success(null, 'Sesión cerrada exitosamente.');
    }

    /**
     * Obtiene el perfil del usuario autenticado, empresa, rol y permisos.
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success(
            new UserResource($request->user()),
            'Información de sesión obtenida con éxito.'
        );
    }

    /**
     * Actualiza la contraseña del usuario previa verificación de la actual.
     * PUT /api/v1/auth/password
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $this->authService->changePassword(
            $request->user(),
            $request->input('current_password'),
            $request->input('password'),
            (bool) $request->input('revoke_other_sessions', false)
        );

        return ApiResponse::success(null, 'Contraseña actualizada exitosamente.');
    }
}
