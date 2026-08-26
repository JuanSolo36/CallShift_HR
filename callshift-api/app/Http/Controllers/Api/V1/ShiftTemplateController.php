<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ShiftTemplate;
use App\Services\Shifts\ShiftPatternService;
use App\Http\Requests\V1\StoreShiftTemplateRequest;
use App\Http\Requests\V1\UpdateShiftTemplateRequest;
use App\Http\Resources\V1\ShiftTemplateResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftTemplateController extends Controller
{
    public function __construct(
        protected ShiftPatternService $patternService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ShiftTemplate::class);

        $templates = $this->patternService->listTemplates($request->user(), $request->query());

        return ApiResponse::success(
            ShiftTemplateResource::collection($templates),
            'Plantillas de turno obtenidas exitosamente.'
        );
    }

    public function store(StoreShiftTemplateRequest $request): JsonResponse
    {
        $this->authorize('create', ShiftTemplate::class);

        $template = $this->patternService->createTemplate($request->validated(), $request->user());

        return ApiResponse::created(
            new ShiftTemplateResource($template),
            'Plantilla de turno creada exitosamente.'
        );
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $template = ShiftTemplate::where('company_id', $request->user()->company_id)
            ->with(['department', 'position', 'pattern.entries.shiftType'])
            ->findOrFail($id);

        $this->authorize('view', $template);

        return ApiResponse::success(
            new ShiftTemplateResource($template),
            'Plantilla de turno obtenida exitosamente.'
        );
    }

    public function update(UpdateShiftTemplateRequest $request, int $id): JsonResponse
    {
        $template = ShiftTemplate::where('company_id', $request->user()->company_id)->findOrFail($id);

        $this->authorize('update', $template);

        $updated = $this->patternService->updateTemplate($template, $request->validated(), $request->user());

        return ApiResponse::success(
            new ShiftTemplateResource($updated),
            'Plantilla de turno actualizada exitosamente.'
        );
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $template = ShiftTemplate::where('company_id', $request->user()->company_id)->findOrFail($id);

        $this->authorize('delete', $template);

        $this->patternService->deleteTemplate($template, $request->user());

        return ApiResponse::success(null, 'Plantilla de turno eliminada exitosamente.');
    }
}
