<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return ApiResponse::unauthorized();
        }

        if (!$user->hasPermission($permission)) {
            return ApiResponse::forbidden("No dispone del permiso requerido [{$permission}] para realizar esta acción.");
        }

        return $next($request);
    }
}
