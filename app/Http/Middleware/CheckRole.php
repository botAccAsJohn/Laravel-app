<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;


class CheckRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->input('role') !== 'admin') {
            return response("Access denied", 403);
        }

        return $next($request);
    }
}
