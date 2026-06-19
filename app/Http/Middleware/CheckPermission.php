<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
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
