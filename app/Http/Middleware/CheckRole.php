<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // For the 'admin' role, check the dedicated admin guard.
        if ($role === 'admin') {
            abort_unless(Auth::guard('admin')->check(), 403);
            return $next($request);
        }

        // For any other role, use the RBAC pivot relationship (role_user table).
        // Calling $user->hasRole() checks $user->roles->pluck('name') which
        // resolves through the BelongsToMany relationship — not the legacy column.
        abort_unless(Auth::user()?->hasRole($role), 403);

        return $next($request);
    }
}
