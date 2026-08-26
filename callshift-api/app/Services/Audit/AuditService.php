<?php

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Models\User;
use App\Enums\AuditAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        'remember_token',
        'access_token',
        'refresh_token',
        'secret',
        'api_key',
        'credentials',
        'authorization',
        'card_number',
        'cvv',
        'pin',
    ];

    /**
     * Registra un evento de auditoría de negocio explícito.
     */
    public static function log(
        AuditAction $action,
        string $auditableType,
        ?int $auditableId = null,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?int $companyId = null,
        ?string $connectionName = null,
        ?User $actor = null
    ): AuditLog {
        $user = $actor ?? Auth::user();
        $targetCompanyId = $companyId ?? ($user?->company_id ?? 1);

        $sanitizedOld = self::sanitizeValues($oldValues);
        $sanitizedNew = self::sanitizeValues($newValues);

        $logModel = new AuditLog();
        if ($connectionName) {
            $logModel->setConnection($connectionName);
        }

        return $logModel->newQuery()->create([
            'company_id'     => $targetCompanyId,
            'user_id'        => $user?->id,
            'action'         => $action->value,
            'auditable_type' => $auditableType,
            'auditable_id'   => $auditableId,
            'description'    => $description ?? "Acción {$action->label()} ejecutada.",
            'old_values'     => $sanitizedOld,
            'new_values'     => $sanitizedNew,
            'ip_address'     => Request::ip(),
            'user_agent'     => Request::userAgent(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Registra la creación de un modelo.
     */
    public static function logCreate(Model $model, ?string $description = null, ?User $actor = null): AuditLog
    {
        $companyId = $model->company_id ?? ($actor?->company_id ?? Auth::user()?->company_id);
        $conn = $model->getConnectionName();

        return self::log(
            AuditAction::CREATE,
            get_class($model),
            $model->id,
            $description ?? "Creación de " . class_basename($model),
            null,
            $model->getAttributes(),
            $companyId,
            $conn,
            $actor
        );
    }

    /**
     * Registra la actualización de un modelo con snapshot simétrico de cambios.
     */
    public static function logUpdate(Model $model, array $oldValues = [], ?string $description = null, ?User $actor = null): AuditLog
    {
        $companyId = $model->company_id ?? ($model->scheduleVersion?->workPeriod?->company_id ?? ($actor?->company_id ?? Auth::user()?->company_id));
        $conn = $model->getConnectionName();

        return self::log(
            AuditAction::UPDATE,
            get_class($model),
            $model->id,
            $description ?? "Actualización de " . class_basename($model),
            $oldValues,
            $model->getChanges(),
            $companyId,
            $conn,
            $actor
        );
    }

    /**
     * Alias compatible con código previo para actualización de modelos.
     */
    public static function logModelUpdated(Model $model, array $oldValues = [], ?string $description = null): AuditLog
    {
        return self::logUpdate($model, $oldValues, $description);
    }

    /**
     * Registra la eliminación de un modelo.
     */
    public static function logDelete(Model $model, ?string $description = null, ?User $actor = null): AuditLog
    {
        $companyId = $model->company_id ?? ($model->scheduleVersion?->workPeriod?->company_id ?? ($actor?->company_id ?? Auth::user()?->company_id));
        $conn = $model->getConnectionName();

        return self::log(
            AuditAction::DELETE,
            get_class($model),
            $model->id,
            $description ?? "Eliminación de " . class_basename($model),
            $model->toArray(),
            null,
            $companyId,
            $conn,
            $actor
        );
    }

    /**
     * Alias compatible con código previo para eliminación de modelos.
     */
    public static function logModelDeleted(Model $model, ?string $description = null): AuditLog
    {
        return self::logDelete($model, $description);
    }

    /**
     * Registra evento de publicación de horario.
     */
    public static function logPublish(
        string $auditableType,
        int $auditableId,
        ?string $description = null,
        ?array $details = null,
        ?int $companyId = null,
        ?string $conn = null,
        ?User $actor = null
    ): AuditLog {
        return self::log(
            AuditAction::PUBLISH,
            $auditableType,
            $auditableId,
            $description ?? "Publicación de {$auditableType} #{$auditableId}",
            null,
            $details,
            $companyId,
            $conn,
            $actor
        );
    }

    /**
     * Registra evento de modificación controlada de horario.
     */
    public static function logModify(
        string $auditableType,
        int $auditableId,
        ?string $description = null,
        ?array $details = null,
        ?int $companyId = null,
        ?string $conn = null,
        ?User $actor = null
    ): AuditLog {
        return self::log(
            AuditAction::MODIFY,
            $auditableType,
            $auditableId,
            $description ?? "Modificación registrada en {$auditableType} #{$auditableId}",
            null,
            $details,
            $companyId,
            $conn,
            $actor
        );
    }

    /**
     * Registra evento de restauración de versión.
     */
    public static function logRestore(
        string $auditableType,
        int $auditableId,
        ?string $description = null,
        ?array $details = null,
        ?int $companyId = null,
        ?string $conn = null,
        ?User $actor = null
    ): AuditLog {
        return self::log(
            AuditAction::RESTORE,
            $auditableType,
            $auditableId,
            $description ?? "Restauración de versión {$auditableType} #{$auditableId}",
            null,
            $details,
            $companyId,
            $conn,
            $actor
        );
    }

    /**
     * Registra evento de exportación de reporte o datos.
     */
    public static function logExport(
        string $auditableType,
        ?string $description = null,
        ?array $filters = null,
        ?int $companyId = null,
        ?User $actor = null
    ): AuditLog {
        return self::log(
            AuditAction::EXPORT,
            $auditableType,
            null,
            $description ?? "Exportación de datos de {$auditableType}",
            null,
            $filters,
            $companyId,
            null,
            $actor
        );
    }

    /**
     * Registra inicio de sesión.
     */
    public static function logLogin(User $user, ?string $ip = null, ?string $userAgent = null): AuditLog
    {
        $logModel = new AuditLog();
        return $logModel->newQuery()->create([
            'company_id'     => $user->company_id ?? 1,
            'user_id'        => $user->id,
            'action'         => AuditAction::LOGIN->value,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'description'    => "Inicio de sesión exitoso para {$user->username} ({$user->email})",
            'old_values'     => null,
            'new_values'     => ['login_at' => now()->toIso8601String()],
            'ip_address'     => $ip ?? Request::ip(),
            'user_agent'     => $userAgent ?? Request::userAgent(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Registra cierre de sesión.
     */
    public static function logLogout(User $user, ?string $ip = null, ?string $userAgent = null): AuditLog
    {
        $logModel = new AuditLog();
        return $logModel->newQuery()->create([
            'company_id'     => $user->company_id ?? 1,
            'user_id'        => $user->id,
            'action'         => AuditAction::LOGOUT->value,
            'auditable_type' => User::class,
            'auditable_id'   => $user->id,
            'description'    => "Cierre de sesión para {$user->username}",
            'old_values'     => null,
            'new_values'     => ['logout_at' => now()->toIso8601String()],
            'ip_address'     => $ip ?? Request::ip(),
            'user_agent'     => $userAgent ?? Request::userAgent(),
            'created_at'     => now(),
        ]);
    }

    /**
     * Consulta registros de auditoría filtrados y paginados bajo aislamiento de tenant.
     */
    public function queryLogs(array $filters, User $actor, int $perPage = 25): LengthAwarePaginator
    {
        return AuditLog::with(['user:id,username,email,first_name,last_name'])
            ->forCompany($actor->company_id)
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(max(1, min($perPage, 100)));
    }

    /**
     * Exporta registros de auditoría en formato CSV respetando el tenant del actor.
     */
    public function exportLogsCsv(array $filters, User $actor): string
    {
        $logs = AuditLog::with(['user:id,username,email'])
            ->forCompany($actor->company_id)
            ->filter($filters)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->limit(5000)
            ->get();

        $output = fopen('php://temp', 'r+');
        fputcsv($output, ['ID', 'Fecha', 'Usuario', 'Accion', 'Entidad', 'ID Entidad', 'Descripcion', 'IP', 'User Agent']);

        foreach ($logs as $log) {
            fputcsv($output, [
                $log->id,
                $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                $log->user ? "{$log->user->username} ({$log->user->email})" : 'Sistema',
                $log->action instanceof AuditAction ? $log->action->value : (string)$log->action,
                class_basename($log->auditable_type),
                $log->auditable_id,
                $log->description,
                $log->ip_address,
                $log->user_agent,
            ]);
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        // Registrar el evento de exportación en la bitácora
        self::logExport(
            AuditLog::class,
            "Exportación de bitácora de auditoría (CSV) por '{$actor->username}'",
            $filters,
            $actor->company_id,
            $actor
        );

        return $csvContent ?: '';
    }

    /**
     * Sanitiza arrays de valores removiendo credenciales, contraseñas y tokens.
     */
    public static function sanitizeValues(?array $values): ?array
    {
        if ($values === null) {
            return null;
        }

        $sanitized = [];
        foreach ($values as $key => $value) {
            $lowerKey = strtolower((string)$key);
            if (in_array($lowerKey, self::SENSITIVE_KEYS, true) || str_contains($lowerKey, 'password') || str_contains($lowerKey, 'secret') || str_contains($lowerKey, 'token')) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeValues($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
