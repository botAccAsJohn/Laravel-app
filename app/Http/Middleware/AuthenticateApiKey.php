<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;


class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (!$bearerToken) {
            return response()->json([
                'error' => 'API key required. Pass Authorization: Bearer <key>',
            ], 401);
        }

        $apiKey = ApiKey::findByPlainKey($bearerToken);

        if (!$apiKey) {
            Log::channel('security')->warning('[ApiKey] Invalid or expired key attempt', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'prefix' => substr($bearerToken, 0, 8), // log prefix only — never full key
            ]);

            return response()->json(['error' => 'Invalid or expired API key.'], 401);
        }

        // Bind the owning user to the request so $request->user() works downstream.
        $request->setUserResolver(fn() => $apiKey->user);

        return $next($request);
    }
}
