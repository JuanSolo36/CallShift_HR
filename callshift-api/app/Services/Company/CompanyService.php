<?php

namespace App\Services\Company;

use App\Models\Company;
use App\Models\SystemSetting;
use App\Models\User;
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\DB;

class CompanyService
{
    /**
     * Obtiene la empresa asociada al usuario autenticado.
     */
    public function getCompany(User $actor): Company
    {
        return Company::findOrFail($actor->company_id);
    }

    /**
     * Actualiza la información corporativa de la empresa.
     */
    public function updateCompany(Company $company, array $data, User $actor): Company
    {
        return DB::transaction(function () use ($company, $data, $actor) {
            $oldValues = $company->getOriginal();

            // Descartar explícitamente cualquier intento de alterar el ID de la empresa
            unset($data['id'], $data['company_id']);

            $company->fill($data);
            $company->save();

            // Auditoría forense segura
            AuditService::logModelUpdated(
                $company,
                $oldValues,
                "Información de la empresa '{$company->name}' actualizada por '{$actor->username}'"
            );

            return $company;
        });
    }

    /**
     * Actualiza las configuraciones regionales, visuales y parámetros del sistema.
     */
    public function updateSettings(Company $company, array $data, User $actor): Company
    {
        return DB::transaction(function () use ($company, $data, $actor) {
            $oldValues = $company->getOriginal();

            // Actualizar campos directos en Company
            $directFields = ['timezone', 'currency', 'date_format', 'primary_color', 'secondary_color'];
            foreach ($directFields as $field) {
                if (isset($data[$field])) {
                    $company->$field = $data[$field];
                }
            }
            $company->save();

            // Actualizar parámetros adicionales en la tabla system_settings
            if (!empty($data['settings']) && is_array($data['settings'])) {
                foreach ($data['settings'] as $key => $value) {
                    $type = is_array($value) ? 'json' : (is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : 'string'));
                    $serializedValue = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string) $value);

                    SystemSetting::updateOrCreate(
                        [
                            'company_id' => $company->id,
                            'key'        => $key,
                        ],
                        [
                            'value' => $serializedValue,
                            'type'  => $type,
                        ]
                    );
                }
            }

            // Auditoría de actualización de configuración
            AuditService::log(
                AuditAction::UPDATE,
                $company,
                $oldValues,
                $company->toArray(),
                "Configuración general de la empresa '{$company->name}' actualizada por '{$actor->username}'"
            );

            return $company;
        });
    }
}
