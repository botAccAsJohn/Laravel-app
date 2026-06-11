<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestOrCustomer
{
    public function handle(Request $request, Closure $next): Response
    {
        // Block admins from the cart/recently-viewed (they use the admin panel)
        if (is_admin()) {
            abort(403, 'Access denied.');
        }

        // Allow guests and all authenticated non-admin users (customers)
        return $next($request);
    }
}