<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class CheckImpersonation
{
    public function handle(Request $request, Closure $next): Response
    {
        if (is_impersonating()) {
            view()->share('isImpersonating', true);

            view()->share('impersonatedUser', impersonated_user());
            view()->share('impersonatingAdmin', impersonating_admin());
        } else {
            view()->share('isImpersonating', false);
            view()->share('impersonatedUser', null);
            view()->share('impersonatingAdmin', null);
        }

        return $next($request);
    }
}
