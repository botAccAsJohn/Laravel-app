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
            if ($request->is('admin/*') && !$request->is('admin/impersonate/stop')) {
                return redirect()->route('products.index')
                    ->with('error', 'You cannot access the admin panel while impersonating a customer. Stop impersonating first.');
            }

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
