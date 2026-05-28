<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * The `role:admin` syntax is now deprecated in favour of the `auth:admin`
     * guard middleware. This middleware is kept for any legacy `role:*` usages
     * but routes should be migrated to use `middleware('auth:admin')` directly.
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // For the 'admin' role, check the dedicated admin guard.
        if ($role === 'admin') {
            abort_unless(Auth::guard('admin')->check(), 403);
            return $next($request);
        }

        // For any other role, fall back to the web guard user's role attribute.
        abort_unless(Auth::user()?->role === $role, 403);

        return $next($request);
    }
}
