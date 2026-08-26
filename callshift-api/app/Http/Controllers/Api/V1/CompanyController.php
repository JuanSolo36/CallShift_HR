<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\V1\UpdateCompanyRequest;
use App\Http\Requests\V1\UpdateCompanySettingsRequest;
use App\Http\Resources\V1\CompanyResource;
use App\Http\Responses\ApiResponse;
use App\Services\Company\CompanyService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService
    ) {}

    /**
     * Consulta la información de la empresa del tenant actual.
     * GET /api/v1/company
     */
    public function show(Request $request): JsonResponse
    {
        $company = $this->companyService->getCompany(Auth::user());
        $this->authorize('view', $company);

        return ApiResponse::success(
            new CompanyResource($company),
            'Información de la empresa obtenida correctamente.'
        );
    }

    /**
     * Actualiza la información corporativa de la empresa.
     * PUT /api/v1/company
     */
    public function update(UpdateCompanyRequest $request): JsonResponse
    {
        $company = $this->companyService->getCompany(Auth::user());
        $this->authorize('update', $company);

        $updatedCompany = $this->companyService->updateCompany(
            $company,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new CompanyResource($updatedCompany),
            'Información corporativa actualizada exitosamente.'
        );
    }

    /**
     * Actualiza las configuraciones regionales, visuales y parámetros del sistema.
     * PATCH /api/v1/company/settings
     */
    public function updateSettings(UpdateCompanySettingsRequest $request): JsonResponse
    {
        $company = $this->companyService->getCompany(Auth::user());
        $this->authorize('manageSettings', $company);

        $updatedCompany = $this->companyService->updateSettings(
            $company,
            $request->validated(),
            Auth::user()
        );

        return ApiResponse::success(
            new CompanyResource($updatedCompany),
            'Configuración del sistema actualizada correctamente.'
        );
    }
}
