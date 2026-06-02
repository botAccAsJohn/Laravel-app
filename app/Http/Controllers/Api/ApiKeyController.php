<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Exercise 53.3 — API Key Management Controller
 *
 * Provides endpoints for users to manage their own API keys:
 *   GET    /api/api-keys          List all keys (hashes hidden, prefix shown)
 *   POST   /api/api-keys          Issue a new key — returns plain-text ONCE
 *   DELETE /api/api-keys/{id}     Revoke a key
 */
class ApiKeyController extends Controller
{
    /**
     * List the authenticated user's API keys.
     *
     * The key_hash is in $hidden on the model — never returned.
     * Only the key_prefix ("sk-abc123…") and metadata are shown.
     */
    public function index(Request $request): JsonResponse
    {
        $keys = $request->user()
            ->apiKeys()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'key_prefix', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json(['data' => $keys]);
    }

    /**
     * Issue a new API key.
     *
     * The plain-text key is returned ONCE in this response.
     * After this request, it is permanently unrecoverable.
     * Users must store it securely (password manager, .env, etc.).
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name'       => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        [$apiKey, $plainText] = ApiKey::generate(
            $request->user(),
            $request->name,
            $request->expires_at ? new \DateTime($request->expires_at) : null,
        );

        Log::channel('security')->info('[ApiKey] New key issued', [
            'user_id'    => $request->user()->id,
            'key_id'     => $apiKey->id,
            'key_prefix' => $apiKey->key_prefix,
            'name'       => $apiKey->name,
        ]);

        return response()->json([
            'message' => 'Store this key immediately — it will not be shown again.',
            'key'     => $plainText,          // plain-text, one-time only
            'data'    => [
                'id'         => $apiKey->id,
                'name'       => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
        ], 201);
    }

    /**
     * Revoke (delete) an API key.
     *
     * Only the owning user can revoke their own keys.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $key = $request->user()->apiKeys()->findOrFail($id);
        $key->delete();

        Log::channel('security')->info('[ApiKey] Key revoked', [
            'user_id'    => $request->user()->id,
            'key_id'     => $id,
            'key_prefix' => $key->key_prefix,
        ]);

        return response()->json(['message' => 'API key revoked.']);
    }
}
