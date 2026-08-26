<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\Events\Lockout;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules for login.
     */
    public function rules(): array
    {
        return [
            'login'       => ['required', 'string'], // Acepta email o username
            'password'    => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'login.required'    => 'El campo de correo electrónico o usuario es obligatorio.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }

    /**
     * Ensure the login request is not rate limited.
     * Maximum 5 attempts per minute per IP + login.
     */
    public function ensureIsNotRateLimited(): void
    {
        if (!RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'login' => ["Demasiados intentos fallidos de inicio de sesión. Por favor intente nuevamente en {$seconds} segundos."],
        ])->status(429);
    }

    /**
     * Increment the login rate limiter attempt counter.
     */
    public function hitRateLimiter(): void
    {
        RateLimiter::hit($this->throttleKey(), 60); // 1 minuto de ventana
    }

    /**
     * Clear the rate limiter for this user/IP.
     */
    public function clearRateLimiter(): void
    {
        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->input('login')) . '|' . $this->ip());
    }
}
