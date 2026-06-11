<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Check that the authenticated user holds the required permission.
     *
     * For the 'admin' guard, admins are granted ALL permissions implicitly
     * (since Admin is a separate model without RBAC pivots).
     *
     * For the 'web' guard (User model), we check the RBAC permission set
     * via User::hasPermission(), which resolves through roles → permissions.
     *
     * Usage:  Route::middleware('permission:manage_products')
     *         Route::middleware('permission:manage_orders,view_reports')  // any of these
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        // Admin guard users have all permissions implicitly.
        if (Auth::guard('admin')->check()) {
            return $next($request);
        }

        $user = Auth::user();
        abort_unless($user, 403);

        // Check if the user has ANY of the listed permissions.
        foreach ($permissions as $permission) {
            if ($user->hasPermission($permission)) {
                return $next($request);
            }
        }

        abort(403, 'You do not have the required permission.');
    }
}
