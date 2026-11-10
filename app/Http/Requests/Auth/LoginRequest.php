<?php

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public const MAX_ATTEMPTS_PER_ACCOUNT = 5;

    public const MAX_ATTEMPTS_PER_IP = 15;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'user_type' => ['nullable', 'string', 'in:cliente,administrador'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(bool $requireAdmin = false): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $user = Auth::user();
        $wantsAdmin = $requireAdmin || $this->routeIs('admin.login.store') || $this->input('user_type') === 'administrador';

        // Erro genérico sem confirmar que a senha estava correta para conta sem privilégio administrativo
        if ($wantsAdmin && ! $user?->is_admin) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->ipThrottleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $accountLimited = RateLimiter::tooManyAttempts($this->throttleKey(), self::MAX_ATTEMPTS_PER_ACCOUNT);
        $ipLimited = RateLimiter::tooManyAttempts($this->ipThrottleKey(), self::MAX_ATTEMPTS_PER_IP);

        if (! $accountLimited && ! $ipLimited) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            RateLimiter::availableIn($this->throttleKey()),
            RateLimiter::availableIn($this->ipThrottleKey())
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    public function ipThrottleKey(): string
    {
        return 'login-ip:'.$this->ip();
    }
}
