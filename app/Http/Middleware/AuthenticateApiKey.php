<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exercise 53.3 — API Key Authentication Middleware
 *
 * Authenticates requests using a SHA-256-hashed API key.
 *
 * Incoming key is taken from the Authorization header:
 *   Authorization: Bearer sk-abc123...
 *
 * Verification flow:
 *   1. Extract plain-text key from the header.
 *   2. Compute hash('sha256', $plain).
 *   3. Look up the hash in the api_keys table (indexed O(1) lookup).
 *   4. hash_equals($stored_hash, $computed_hash) — constant-time comparison.
 *   5. Check expiry.
 *   6. Bind the owning User to the request so downstream controllers can use
 *      $request->user() as normal.
 */
class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $bearerToken = $request->bearerToken();

        if (! $bearerToken) {
            return response()->json([
                'error' => 'API key required. Pass Authorization: Bearer <key>',
            ], 401);
        }

        $apiKey = ApiKey::findByPlainKey($bearerToken);

        if (! $apiKey) {
            Log::channel('security')->warning('[ApiKey] Invalid or expired key attempt', [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
                'prefix'     => substr($bearerToken, 0, 8), // log prefix only — never full key
            ]);

            return response()->json(['error' => 'Invalid or expired API key.'], 401);
        }

        // Bind the owning user to the request so $request->user() works downstream.
        $request->setUserResolver(fn () => $apiKey->user);

        return $next($request);
    }
}
