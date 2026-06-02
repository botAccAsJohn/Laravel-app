<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exercise 52.3 — RequirePasswordReset Middleware
 *
 * On EVERY authenticated request this middleware checks whether the current
 * web-guard user has force_password_reset = true. If so, every route except
 * the password-reset flow is blocked and the user is redirected to the
 * forgot-password page with a clear message.
 *
 * Why block ALL routes, not just sensitive ones?
 * ─────────────────────────────────────────────
 * A compromised account is compromised on every endpoint. Allowing access
 * to "harmless" pages (profile view, product list) still lets an attacker
 * read private data. The only safe approach is a complete block until the
 * password is changed.
 *
 * Why must forced reset also invalidate other sessions? (Deliverable)
 * ────────────────────────────────────────────────────────────────────
 * Setting force_password_reset = true does NOT automatically disconnect any
 * session the attacker already has. Without logoutOtherDevices() (called in
 * NewPasswordController after a successful reset), the attacker's session
 * remains active even after the user changes the password. The reset MUST
 * destroy all other sessions to guarantee the attacker is ejected.
 *
 * When is forced reset appropriate? (Deliverable)
 * ─────────────────────────────────────────────────
 * 1. Compromised account — admin detects suspicious activity (login from
 *    unusual country, credential stuffing match, data breach notification).
 * 2. Policy change — the org mandates minimum password length/complexity
 *    retroactively; all users with non-compliant passwords must reset.
 * 3. Credential rotation — periodic forced rotation after N days for
 *    regulated industries (healthcare, finance).
 * 4. Shared-secret incident — a service credential was exposed and all
 *    users who used the same password must be forced to change.
 */
class RequirePasswordReset
{
    /** Routes that are always permitted even during a forced reset. */
    private array $except = [
        'password.request',   // GET  /forgot-password
        'password.email',     // POST /forgot-password
        'password.reset',     // GET  /reset-password/{token}
        'password.store',     // POST /reset-password
        'logout',             // POST /logout
        'verification.notice',
        'verification.verify',
        'verification.send',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::guard('web')->user();

        // Skip if unauthenticated, or if the flag is not set.
        if (! $user || ! $user->force_password_reset) {
            return $next($request);
        }

        // Skip the reset / logout routes themselves.
        if ($request->routeIs(...$this->except)) {
            return $next($request);
        }

        // All other routes: hard-redirect to the forgot-password page
        // with a prominent warning message.
        return redirect()
            ->route('password.request')
            ->with(
                'force_reset_warning',
                'Your account has been flagged for a mandatory password reset. '
                . 'Please reset your password to continue.'
            );
    }
}
