<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckImpersonation middleware
 *
 * Runs on every web request. When an admin impersonation session is active it:
 *   1. Shares $isImpersonating = true with all Blade views so any template can
 *      show a banner or adjust its output without repeating session() calls.
 *   2. Shares $impersonatedUser and $impersonatingAdmin via the same helpers
 *      used everywhere else in the app — single source of truth.
 *
 * The middleware itself never redirects or blocks — it is purely informational.
 * Authorization is enforced inside ImpersonationController via abort_unless().
 *
 * Session key: 'impersonator_id' (set by ImpersonationController::impersonate,
 *               cleared by ImpersonationController::stopImpersonating via pull()).
 */
class CheckImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_impersonating()) {
            // Share flat boolean — cheapest possible check in every blade.
            view()->share('isImpersonating', true);

            // Share the resolved models so templates don't call the helpers
            // multiple times (each helper call is already cheap, but sharing
            // ensures a consistent snapshot for the duration of the request).
            view()->share('impersonatedUser',   impersonated_user());
            view()->share('impersonatingAdmin', impersonating_admin());
        } else {
            // Always define the variables so templates never hit an undefined
            // variable error even when the @if guard is accidentally omitted.
            view()->share('isImpersonating',    false);
            view()->share('impersonatedUser',   null);
            view()->share('impersonatingAdmin', null);
        }

        return $next($request);
    }
}
