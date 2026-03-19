<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class CheckRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if ($request->input('role') !== $role) {
            return response("Access denied", 403);
        }

        return $next($request);
    }
}
