<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Http\Responses\ApiResponse;

class EnsureCompanyActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->company && $user->company->status !== 'ACTIVE') {
            return ApiResponse::forbidden('La empresa vinculada se encuentra inactiva o suspendida.');
        }

        return $next($request);
    }
}
