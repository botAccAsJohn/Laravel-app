<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiKeyController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $keys = $request->user()
            ->apiKeys()
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'key_prefix', 'last_used_at', 'expires_at', 'created_at']);

        return response()->json(['data' => $keys]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        [$apiKey, $plainText] = ApiKey::generate(
            $request->user(),
            $request->name,
            $request->expires_at ? new \DateTime($request->expires_at) : null,
        );

        Log::channel('security')->info('[ApiKey] New key issued', [
            'user_id' => $request->user()->id,
            'key_id' => $apiKey->id,
            'key_prefix' => $apiKey->key_prefix,
            'name' => $apiKey->name,
        ]);

        return response()->json([
            'message' => 'Store this key immediately — it will not be shown again.',
            'key' => $plainText,          // plain-text, one-time only
            'data' => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'key_prefix' => $apiKey->key_prefix,
                'expires_at' => $apiKey->expires_at,
                'created_at' => $apiKey->created_at,
            ],
        ], 201);
    }


    public function destroy(Request $request, int $id): JsonResponse
    {
        $key = $request->user()->apiKeys()->findOrFail($id);
        $key->delete();

        Log::channel('security')->info('[ApiKey] Key revoked', [
            'user_id' => $request->user()->id,
            'key_id' => $id,
            'key_prefix' => $key->key_prefix,
        ]);

        return response()->json(['message' => 'API key revoked.']);
    }
}
