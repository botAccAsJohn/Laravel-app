<?php

namespace App\Http\Middleware;

use Auth;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ReqContextMiddleware
{

    public function handle(Request $request, Closure $next): Response
    {
        // Generate request ID
        $requestId = (string) Str::ulid();
        $user = $request->user();

        Context::add('request_id', $requestId);
        Context::add('user_id', $user?->id);
        Context::add('user_type', $user?->role ?? 'user');
        Context::add('ip_address', $request->ip());

        $userType = Context::get('user_type', 'customer');

        Log::channel($userType)->info("{$userType} performed an action");

        return $next($request);
    }
}
