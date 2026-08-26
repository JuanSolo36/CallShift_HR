<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Enums\AuditAction;
use App\Services\Audit\AuditService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Autentica a un usuario y genera su token de sesión.
     */
    public function login(string $login, string $password, ?string $deviceName, ?string $ip, ?string $userAgent): array
    {
        // Buscar por email o username (sin aplicar TenantScope porque en login aún no hay sesión)
        $user = User::withoutGlobalScopes()
            ->with(['company', 'role.permissions', 'employee.department', 'employee.position'])
            ->where(function ($query) use ($login) {
                $query->where('email', $login)
                      ->orWhere('username', $login);
            })
            ->first();

        // 1. Validar existencia y coincidencia de contraseña
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'login' => ['Credenciales de acceso inválidas.'],
            ]);
        }

        // 2. Validar que el usuario esté activo
        if ($user->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'login' => ['Su cuenta de usuario se encuentra inactiva o suspendida. Contacte al administrador.'],
            ]);
        }

        // 3. Validar que la empresa esté activa
        if ($user->company && $user->company->status !== 'ACTIVE') {
            throw ValidationException::withMessages([
                'login' => ['La empresa asociada a su cuenta se encuentra inactiva o suspendida.'],
            ]);
        }

        // 4. Generar Token Sanctum
        $tokenName = $deviceName ? "callshift_{$deviceName}" : 'callshift_web_session';
        $token = $user->createToken($tokenName)->plainTextToken;

        // 5. Actualizar marca temporal de último acceso
        $user->updateQuietly(['last_login_at' => now()]);

        // 6. Registrar Auditoría Forense de Inicio de Sesión
        AuditService::log(
            AuditAction::LOGIN,
            User::class,
            $user->id,
            "Inicio de sesión exitoso para el usuario {$user->username} ({$user->email})",
            null,
            ['login_at' => now()->toIso8601String(), 'device' => $tokenName],
            $user->company_id ?? 1
        );

        return [
            'user'  => $user,
            'token' => $token,
        ];
    }

    /**
     * Cierra la sesión activa revocando el token de Sanctum.
     */
    public function logout(User $user, ?string $ip, ?string $userAgent): void
    {
        // Revocar el token que está usando actualmente la petición
        if ($user->currentAccessToken()) {
            $user->currentAccessToken()->delete();
        }

        // Registrar Auditoría Forense de Cierre de Sesión
        AuditService::log(
            AuditAction::LOGOUT,
            User::class,
            $user->id,
            "Cierre de sesión para el usuario {$user->username}",
            null,
            ['logout_at' => now()->toIso8601String()],
            $user->company_id ?? 1
        );
    }

    /**
     * Modifica la contraseña del usuario previa verificación de la actual.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword, bool $revokeOtherSessions = false): void
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual proporcionada es incorrecta.'],
            ]);
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        if ($revokeOtherSessions && $user->currentAccessToken()) {
            $currentTokenId = $user->currentAccessToken()->id;
            $user->tokens()->where('id', '!=', $currentTokenId)->delete();
        }

        AuditService::log(
            AuditAction::UPDATE,
            User::class,
            $user->id,
            "Cambio de contraseña realizado para el usuario {$user->username}",
            ['password' => '****** (actualizada)'],
            ['password' => '****** (nueva)'],
            $user->company_id ?? 1
        );
    }
}
