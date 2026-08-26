<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShiftPattern;
use App\Services\Shifts\ShiftPatternService;
use App\Http\Requests\V1\StoreShiftPatternRequest;
use App\Http\Requests\V1\UpdateShiftPatternRequest;
use App\Http\Resources\V1\ShiftPatternResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ShiftPatternController extends Controller
{
    public function __construct(
        protected ShiftPatternService $patternService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ShiftPattern::class);

        $patterns = $this->patternService->listPatterns($request->user(), $request->query());

        return ApiResponse::success(
            ShiftPatternResource::collection($patterns),
            'Patrones de turno obtenidos exitosamente.'
        );
    }

    public function store(StoreShiftPatternRequest $request): JsonResponse
    {
        $this->authorize('create', ShiftPattern::class);

        $pattern = $this->patternService->createPattern($request->validated(), $request->user());

        return ApiResponse::created(
            new ShiftPatternResource($pattern),
            'Patrón de turno creado exitosamente.'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $pattern = ShiftPattern::where('company_id', $request->user()->company_id)
            ->with(['department', 'position', 'entries.shiftType'])
            ->findOrFail($id);

        $this->authorize('view', $pattern);

        return ApiResponse::success(
            new ShiftPatternResource($pattern),
            'Patrón de turno obtenido exitosamente.'
        );
    }

    public function update(UpdateShiftPatternRequest $request, int $id): JsonResponse
    {
        $pattern = ShiftPattern::where('company_id', $request->user()->company_id)->findOrFail($id);

        $this->authorize('update', $pattern);

        $updated = $this->patternService->updatePattern($pattern, $request->validated(), $request->user());

        return ApiResponse::success(
            new ShiftPatternResource($updated),
            'Patrón de turno actualizado exitosamente.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $pattern = ShiftPattern::where('company_id', $request->user()->company_id)->findOrFail($id);

        $this->authorize('delete', $pattern);

        $this->patternService->deletePattern($pattern, $request->user());

        return ApiResponse::success(null, 'Patrón de turno eliminado exitosamente.');
    }
}
