<?php

namespace App\Http\Middleware;

use closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogRequestLifecycle
{
    public function handle(Request $request, Closure $next)
    {
        // [STEP 4/7] Log middleware entry
        Log::info('[STEP 4] Middleware handle() BEFORE controller', [
            'url' => $request->fullurl(),
            'method' => $request->method(),
            'ip' => $request->ip(),
        ]);

        // Pass request to next middleware / controller
        $response = $next($request);
        // Log on the way back out (after controller)
        Log::info('[STEP 9-pre] Middleware handle() AFTER controller, returning
response');
        return $response;
    }
}
