<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponse
{
    /**
     * Responde con éxito estandarizado.
     */
    public static function success(
        mixed $data = null,
        string $message = 'Operación realizada con éxito.',
        int $statusCode = Response::HTTP_OK
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Responde con recurso creado exitosamente.
     */
    public static function created(
        mixed $data = null,
        string $message = 'Recurso creado exitosamente.'
    ): JsonResponse {
        return self::success($data, $message, Response::HTTP_CREATED);
    }

    /**
     * Responde con error estructurado.
     */
    public static function error(
        string $message = 'Ha ocurrido un error en la solicitud.',
        int $statusCode = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ): JsonResponse {
        $payload = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $statusCode);
    }

    /**
     * Error 401 No Autorizado.
     */
    public static function unauthorized(string $message = 'No autorizado / Sesión no válida.'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNAUTHORIZED);
    }

    /**
     * Error 403 Prohibido / Sin Permisos.
     */
    public static function forbidden(string $message = 'No tiene permisos para realizar esta acción.'): JsonResponse
    {
        return self::error($message, Response::HTTP_FORBIDDEN);
    }

    /**
     * Error 404 No Encontrado.
     */
    public static function notFound(string $message = 'El recurso solicitado no fue encontrado.'): JsonResponse
    {
        return self::error($message, Response::HTTP_NOT_FOUND);
    }

    /**
     * Error 422 Validación de Datos.
     */
    public static function validationError(mixed $errors, string $message = 'Los datos proporcionados no son válidos.'): JsonResponse
    {
        return self::error($message, Response::HTTP_UNPROCESSABLE_ENTITY, $errors);
    }
}
