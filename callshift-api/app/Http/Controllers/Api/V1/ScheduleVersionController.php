<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreScheduleVersionRequest;
use App\Http\Requests\V1\ReviewScheduleVersionRequest;
use App\Http\Requests\V1\ReturnToDraftScheduleVersionRequest;
use App\Http\Requests\V1\PublishScheduleVersionRequest;
use App\Http\Requests\V1\RestoreScheduleVersionRequest;
use App\Http\Responses\ApiResponse;
use App\Models\WorkPeriod;
use App\Models\ScheduleVersion;
use App\Services\Schedule\ScheduleVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleVersionController extends Controller
{
    public function __construct(
        protected ScheduleVersionService $versionService
    ) {}

    public function index(int $workPeriodId, Request $request): JsonResponse
    {
        $period = WorkPeriod::where('company_id', $request->user()->company_id)->findOrFail($workPeriodId);
        $this->authorize('view', $period);

        $versions = $this->versionService->listVersions($period, $request->user());

        return ApiResponse::success($versions, 'Listado de versiones obtenido correctamente.');
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($id);
        $this->authorize('view', $version);

        $data = $this->versionService->getVersion($id, $request->user());

        return ApiResponse::success($data, 'Detalle de la versión obtenido correctamente.');
    }

    public function store(StoreScheduleVersionRequest $request, int $workPeriodId): JsonResponse
    {
        $period = WorkPeriod::where('company_id', $request->user()->company_id)->findOrFail($workPeriodId);
        $this->authorize('create', [ScheduleVersion::class, $period]);

        $sourceVersion = null;
        if ($request->filled('source_version_id')) {
            $sourceVersion = ScheduleVersion::where('work_period_id', $period->id)->findOrFail($request->integer('source_version_id'));
        }

        $version = $this->versionService->createDraftFromVersion(
            $period,
            $sourceVersion,
            $request->input('change_summary'),
            $request->user()
        );

        return ApiResponse::created($version, 'Borrador de horario creado exitosamente.');
    }

    public function review(ReviewScheduleVersionRequest $request, int $id): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($id);
        $this->authorize('review', $version);

        $updated = $this->versionService->reviewVersion(
            $version,
            $request->integer('lock_version'),
            $request->input('notes'),
            $request->user()
        );

        return ApiResponse::success($updated, 'Versión de horario enviada a revisión correctamente.');
    }

    public function returnToDraft(ReturnToDraftScheduleVersionRequest $request, int $id): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($id);
        $this->authorize('review', $version);

        $updated = $this->versionService->returnToDraft(
            $version,
            $request->integer('lock_version'),
            $request->input('reason'),
            $request->user()
        );

        return ApiResponse::success($updated, 'Versión de horario devuelta a borrador correctamente.');
    }

    public function publish(PublishScheduleVersionRequest $request, int $id): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($id);
        $this->authorize('publish', $version);

        $published = $this->versionService->publishVersion(
            $version,
            $request->integer('lock_version'),
            $request->input('change_summary'),
            $request->user()
        );

        return ApiResponse::success($published, 'Horario publicado oficialmente exitosamente.');
    }

    public function restore(RestoreScheduleVersionRequest $request, int $workPeriodId): JsonResponse
    {
        $period = WorkPeriod::where('company_id', $request->user()->company_id)->findOrFail($workPeriodId);
        $this->authorize('restore', [ScheduleVersion::class, $period]);

        $targetVersion = ScheduleVersion::where('work_period_id', $period->id)->findOrFail($request->integer('target_version_id'));

        $restored = $this->versionService->restoreVersion(
            $period,
            $targetVersion,
            $request->input('reason'),
            $request->user()
        );

        return ApiResponse::created($restored, 'Versión histórica restaurada como nuevo borrador exitosamente.');
    }

    public function compare(int $id, int $otherVersionId, Request $request): JsonResponse
    {
        $versionA = ScheduleVersion::with(['workPeriod'])->findOrFail($id);
        $versionB = ScheduleVersion::with(['workPeriod'])->findOrFail($otherVersionId);

        $this->authorize('view', $versionA);
        $this->authorize('view', $versionB);

        $diffData = $this->versionService->compareVersions($versionA, $versionB, $request->user());

        return ApiResponse::success($diffData, 'Comparación de versiones calculada exitosamente.');
    }
}
