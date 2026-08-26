<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized();
        }

        if (!$user->hasRole($roles)) {
            return ApiResponse::forbidden('Su rol actual no cuenta con privilegios suficientes para acceder a este recurso.');
        }

        return $next($request);
    }
}
