<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\StoreBusinessRuleRequest;
use App\Http\Requests\V1\UpdateBusinessRuleRequest;
use App\Http\Resources\V1\BusinessRuleResource;
use App\Models\BusinessRule;
use App\Services\Conflicts\BusinessRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BusinessRuleController extends Controller
{
    public function __construct(
        protected BusinessRuleService $ruleService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BusinessRule::class);

        $rules = $this->ruleService->listForCompany((int) $request->user()->company_id);

        return response()->json([
            'success' => true,
            'data'    => BusinessRuleResource::collection($rules),
        ]);
    }

    public function show(BusinessRule $businessRule): JsonResponse
    {
        $this->authorize('view', $businessRule);

        $businessRule->load('department');

        return response()->json([
            'success' => true,
            'data'    => new BusinessRuleResource($businessRule),
        ]);
    }

    public function store(StoreBusinessRuleRequest $request): JsonResponse
    {
        $this->authorize('create', BusinessRule::class);

        $data = $request->validated();
        $scopeId = !empty($data['department_id']) ? (int) $data['department_id'] : 0;
        $existed = BusinessRule::where('company_id', $request->user()->company_id)->where('department_scope_id', $scopeId)->exists();

        $rule = $this->ruleService->createOrUpdate($data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Regla de negocio guardada exitosamente.',
            'data'    => new BusinessRuleResource($rule),
        ], $existed ? Response::HTTP_OK : Response::HTTP_CREATED);
    }

    public function update(UpdateBusinessRuleRequest $request, BusinessRule $businessRule): JsonResponse
    {
        $this->authorize('update', $businessRule);

        $data = array_merge($request->validated(), [
            'department_id' => $businessRule->department_id,
        ]);

        $rule = $this->ruleService->createOrUpdate($data, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Regla de negocio actualizada exitosamente.',
            'data'    => new BusinessRuleResource($rule),
        ]);
    }

    public function destroy(BusinessRule $businessRule, Request $request): JsonResponse
    {
        $this->authorize('delete', $businessRule);

        $this->ruleService->delete($businessRule, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Regla de negocio eliminada exitosamente.',
        ]);
    }
}
