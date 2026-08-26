<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ScheduleVersion;
use App\Services\Shifts\PatternApplicationService;
use App\Http\Requests\V1\PreviewPatternApplicationRequest;
use App\Http\Requests\V1\ApplyPatternRequest;
use App\Http\Resources\V1\PatternPreviewResource;
use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class PatternApplicationController extends Controller
{
    public function __construct(
        protected PatternApplicationService $applicationService
    ) {}

    public function preview(PreviewPatternApplicationRequest $request, int $versionId): JsonResponse
    {
        $version = ScheduleVersion::with('workPeriod')->findOrFail($versionId);

        $this->authorize('update', $version);

        $previewData = $this->applicationService->preview(
            $version,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            new PatternPreviewResource($previewData),
            'Simulación de aplicación de patrón calculada exitosamente.'
        );
    }

    public function apply(ApplyPatternRequest $request, int $versionId): JsonResponse
    {
        $version = ScheduleVersion::with('workPeriod')->findOrFail($versionId);

        $this->authorize('update', $version);

        $result = $this->applicationService->apply(
            $version,
            $request->validated(),
            $request->user()
        );

        return ApiResponse::success(
            $result,
            $result['message']
        );
    }
}
