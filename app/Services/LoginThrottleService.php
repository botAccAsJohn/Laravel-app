<?php
// app/Services/LoginThrottleService.php
//
// Exercise 49.4 — Account-based brute-force protection.
//
// This service operates on TWO independent dimensions:
//
//   1. IP+email  (already handled by the 'throttle:login' middleware in AppServiceProvider)
//      → Hard 429 response after 5 req / 5 min.
//
//   2. Account   (this service — email-keyed, IP-agnostic)
//      → Soft lockout: email warning at attempt 5.
//      → CAPTCHA gate: required at attempt 10+.
//      → Both counts reset on successful login.
//
// Storing counters in Cache (Redis in production) means they survive
// between requests without a DB write on every attempt.

namespace App\Services;

use App\Models\User;
use App\Notifications\LoginAttemptWarning;
use Illuminate\Support\Facades\{Cache, Log, Notification};

class LoginThrottleService
{
    // ── Tuneable constants ────────────────────────────────────────────────
    public const WARN_AFTER     = 5;   // send email warning at this failure count
    public const CAPTCHA_AFTER  = 10;  // require CAPTCHA at this count
    public const WINDOW_MINUTES = 15;  // sliding window for account counter

    // ── Cache key helpers ─────────────────────────────────────────────────

    /** Account-keyed failure counter (email only, IP-agnostic). */
    public static function accountKey(string $email): string
    {
        return 'login_failures:' . strtolower(trim($email));
    }

    /** Flag set after the warning email has been sent for this window. */
    private static function warnedKey(string $email): string
    {
        return 'login_warned:' . strtolower(trim($email));
    }

    // ── Core public API ───────────────────────────────────────────────────

    /**
     * Record a FAILED login attempt for the given email.
     *
     * Side-effects (triggered once per threshold crossing):
     *   • At WARN_AFTER  → dispatch LoginAttemptWarning notification.
     *   • Always         → write to security log.
     *
     * @return int  The new failure count after recording this attempt.
     */
    public function recordFailure(string $email, string $ip, string $userAgent = ''): int
    {
        $key   = self::accountKey($email);
        $count = (int) Cache::get($key, 0) + 1;

        Cache::put($key, $count, now()->addMinutes(self::WINDOW_MINUTES));

        // ── Security log (Module 25) ──────────────────────────────────────
        Log::channel('security')->warning('[LoginThrottle] Failed login attempt', [
            'email'    => $email,
            'ip'       => $ip,
            'attempts' => $count,
            'ua'       => $userAgent,
        ]);

        // ── Email warning threshold ───────────────────────────────────────
        if ($count === self::WARN_AFTER) {
            $this->dispatchWarning($email, $ip, $count, $userAgent);
        }

        return $count;
    }

    /**
     * Record a SUCCESSFUL login.
     * Clears the account failure counter and logs the event.
     */
    public function recordSuccess(string $email, string $ip, int $userId): void
    {
        Cache::forget(self::accountKey($email));
        Cache::forget(self::warnedKey($email));

        Log::channel('security')->info('[LoginThrottle] Successful login', [
            'email'   => $email,
            'user_id' => $userId,
            'ip'      => $ip,
        ]);
    }

    /**
     * How many times has this account failed in the current window?
     */
    public function failureCount(string $email): int
    {
        return (int) Cache::get(self::accountKey($email), 0);
    }

    /**
     * Is CAPTCHA required for this email right now?
     */
    public function requiresCaptcha(string $email): bool
    {
        return $this->failureCount($email) >= self::CAPTCHA_AFTER;
    }

    // ── Internal helpers ──────────────────────────────────────────────────

    /**
     * Send the warning notification once per window using a "warned" flag.
     * Without the flag, every subsequent failure after the 5th would re-send.
     */
    private function dispatchWarning(
        string $email,
        string $ip,
        int    $count,
        string $userAgent
    ): void {
        $warnedKey = self::warnedKey($email);

        // Guard: send only once per WINDOW_MINUTES window.
        if (Cache::has($warnedKey)) {
            return;
        }

        Cache::put($warnedKey, true, now()->addMinutes(self::WINDOW_MINUTES));

        $user = User::where('email', $email)->first();
        if (! $user) {
            // Don't reveal user existence through timing; log silently.
            Log::channel('security')->info('[LoginThrottle] Warning skipped — email not found', [
                'email' => $email, 'ip' => $ip,
            ]);
            return;
        }

        $user->notify(new LoginAttemptWarning(
            ip:             $ip,
            failedAttempts: $count,
            userAgent:      $userAgent,
        ));

        Log::channel('security')->warning('[LoginThrottle] Warning email dispatched', [
            'email'   => $email,
            'user_id' => $user->id,
            'ip'      => $ip,
        ]);
    }
}
