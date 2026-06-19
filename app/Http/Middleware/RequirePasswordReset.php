<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

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
        if (!$user || !$user->force_password_reset) {
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
