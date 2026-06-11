<?php
// app/Http/Requests/Auth/LoginRequest.php
//
// Exercise 49.4 — upgraded Breeze LoginRequest with:
//   • Account-based throttle via LoginThrottleService
//   • hCaptcha verification after 10 failures
//   • Security-log on every attempt (success + failure)

namespace App\Http\Requests\Auth;

use App\Services\LoginThrottleService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\{Auth, Http, Log, RateLimiter};
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * h-captcha-response is conditionally required only when the account
     * has hit 10+ failures; validated inline in authenticate() for UX clarity.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * Layer 1 (IP+email) — handled upstream by throttle:login middleware.
     * Layer 2 (account)  — handled here via LoginThrottleService.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        // ── Layer 1: IP-based rate limit (Breeze-style) ───────────────────
        $this->ensureIsNotRateLimited();

        $email   = strtolower(trim($this->string('email')));
        $service = app(LoginThrottleService::class);

        // ── CAPTCHA gate (account-based, after 10 failures) ───────────────
        if ($service->requiresCaptcha($email)) {
            $this->verifyCaptcha();
        }

        // ── Auth attempt ──────────────────────────────────────────────────
        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {

            // IP-based counter (Breeze layer 1)
            RateLimiter::hit($this->throttleKey());

            // Account-based counter (layer 2 — new)
            $service->recordFailure($email, $this->ip(), $this->userAgent() ?? '');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // ── Success: clear both counters ──────────────────────────────────
        RateLimiter::clear($this->throttleKey());
                // Transparent re-hash on password-algorithm change (Exercise 53.2)
        if (\Illuminate\Support\Facades\Hash::needsRehash(Auth::user()->password)) {
            Auth::user()->update([
                'password' => \Illuminate\Support\Facades\Hash::make($this->password),
            ]);
            Log::channel('security')->info('[Password] Transparent re-hash performed', [
                'user_id' => Auth::id(),
                'new_algo' => config('hashing.driver'),
            ]);
        }
    }

    // ── IP-based rate limiter (Layer 1 — unchanged Breeze logic) ─────────

    /**
     * Ensure the login request is not IP-rate-limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * IP+email combined key for the Breeze-style IP rate limiter.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')) . '|' . $this->ip());
    }

    // ── hCaptcha verification (Layer 2, after 10 account failures) ────────

    /**
     * Verify the hCaptcha token submitted with the form.
     *
     * Uses hCaptcha's server-side siteverify endpoint.
     * Falls back gracefully when HCAPTCHA_SECRET is not set (local dev).
     *
     * @throws ValidationException
     */
    private function verifyCaptcha(): void
    {
        $secret = config('services.hcaptcha.secret');

        // In local/testing environments without hCaptcha configured, skip
        // verification so developers are not blocked.
        if (empty($secret)) {
            Log::channel('security')->debug('[LoginThrottle] hCaptcha skipped — HCAPTCHA_SECRET not set');
            return;
        }

        $token = $this->input('h-captcha-response');

        if (empty($token)) {
            Log::channel('security')->warning('[LoginThrottle] CAPTCHA token missing', [
                'email' => $this->input('email'),
                'ip'    => $this->ip(),
            ]);
            throw ValidationException::withMessages([
                'captcha' => 'Please complete the CAPTCHA challenge to continue.',
            ]);
        }

        $response = Http::asForm()->post('https://hcaptcha.com/siteverify', [
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $this->ip(),
        ]);

        if (! $response->successful() || ! $response->json('success')) {
            Log::channel('security')->warning('[LoginThrottle] CAPTCHA verification failed', [
                'email'  => $this->input('email'),
                'ip'     => $this->ip(),
                'errors' => $response->json('error-codes'),
            ]);
            throw ValidationException::withMessages([
                'captcha' => 'CAPTCHA verification failed. Please try again.',
            ]);
        }
    }
}
