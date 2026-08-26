<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreScheduleModificationRequest;
use App\Http\Requests\V1\AttachModificationEvidenceRequest;
use App\Http\Resources\V1\ScheduleModificationResource;
use App\Http\Resources\V1\ModificationEvidenceResource;
use App\Http\Responses\ApiResponse;
use App\Models\ScheduleVersion;
use App\Models\ScheduleModification;
use App\Models\ModificationEvidence;
use App\Services\Schedule\ScheduleModificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ScheduleModificationController extends Controller
{
    public function __construct(
        protected ScheduleModificationService $modificationService
    ) {}

    public function index(int $versionId, Request $request): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($versionId);
        $this->authorize('view', $version);

        $modifications = $this->modificationService->listModifications($version, $request->user());

        return ApiResponse::success(
            ScheduleModificationResource::collection($modifications),
            'Listado de modificaciones obtenido exitosamente.'
        );
    }

    public function store(StoreScheduleModificationRequest $request, int $versionId): JsonResponse
    {
        $version = ScheduleVersion::with(['workPeriod'])->findOrFail($versionId);
        $this->authorize('create', [ScheduleModification::class, $version]);

        $files = $request->file('evidences', []);
        if (!is_array($files)) {
            $files = $files ? [$files] : [];
        }

        $result = $this->modificationService->createModification(
            $version,
            $request->validated(),
            $files,
            $request->user()
        );

        return ApiResponse::created([
            'modification'        => new ScheduleModificationResource($result['modification']),
            'resulting_version'   => $result['resulting_version'],
            'created_new_version' => $result['created_version'],
            'conflicts_count'     => $result['conflicts']->count(),
        ], 'Modificación de horario registrada exitosamente.');
    }

    public function show(int $id, Request $request): JsonResponse
    {
        $modification = $this->modificationService->getModification($id, $request->user());
        $this->authorize('view', $modification);

        return ApiResponse::success(
            new ScheduleModificationResource($modification),
            'Detalle de la modificación obtenido exitosamente.'
        );
    }

    public function attachEvidence(AttachModificationEvidenceRequest $request, int $id): JsonResponse
    {
        $modification = ScheduleModification::with(['version.workPeriod'])->findOrFail($id);
        $this->authorize('attachEvidence', $modification);

        $evidence = $this->modificationService->attachEvidence(
            $modification,
            $request->file('file'),
            $request->user()
        );

        return ApiResponse::created(
            new ModificationEvidenceResource($evidence),
            'Evidencia documental adjuntada exitosamente.'
        );
    }

    public function downloadEvidence(int $id, int $evidenceId, Request $request): BinaryFileResponse
    {
        $evidence = ModificationEvidence::with(['modification.version.workPeriod'])
            ->where('schedule_modification_id', $id)
            ->findOrFail($evidenceId);

        $this->authorize('downloadEvidence', $evidence);

        $downloadData = $this->modificationService->downloadEvidence($evidence, $request->user());

        return response()->download(
            $downloadData['absolute_path'],
            $downloadData['original_name'],
            ['Content-Type' => $downloadData['mime_type']]
        );
    }

    public function destroyEvidence(int $id, int $evidenceId, Request $request): JsonResponse
    {
        $evidence = ModificationEvidence::with(['modification.version.workPeriod'])
            ->where('schedule_modification_id', $id)
            ->findOrFail($evidenceId);

        $this->authorize('deleteEvidence', $evidence);

        $this->modificationService->deleteEvidence($evidence, $request->user());

        return ApiResponse::success(null, 'Evidencia eliminada exitosamente.');
    }
}
