<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use App\Http\Responses\ApiResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: null,
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
    )
    ->withMiddleware(function (Middleware $middleware) {
        
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'company.active' => \App\Http\Middleware\EnsureCompanyActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (ValidationException $e) {
            return ApiResponse::validationError($e->errors(), 'Errores de validación en los campos enviados.');
        });

        $exceptions->render(function (AuthenticationException $e) {
            return ApiResponse::unauthorized('Sesión no válida o token expirado.');
        });

        $exceptions->render(function (AccessDeniedHttpException|AuthorizationException $e) {
            return ApiResponse::forbidden('Acceso denegado: no dispone de los privilegios requeridos.');
        });

        $exceptions->render(function (NotFoundHttpException $e) {
            return ApiResponse::notFound('El endpoint o recurso solicitado no existe.');
        });
    })->create();
