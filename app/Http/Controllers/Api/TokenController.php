<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TokenController extends Controller
{
    public function index(Request $request)
    {
        $tokens = $request->user()
            ->tokens()
            ->select(['id', 'name', 'last_used_at', 'created_at', 'expires_at'])
            ->get()
            ->map(function ($token) use ($request) {
                return [
                    ...$token->toArray(),
                    'is_current' => $token->id === $request->user()->currentAccessToken()->id,
                ];
            });

        return response()->json($tokens);
    }

    public function revoke(Request $request, int $id)
    {
        $token = $request->user()->tokens()->findOrFail($id);
        $token->delete();

        return response()->json(['message' => "Token {$id} revoked."]);
    }

    public function revokeAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'All tokens revoked.']);
    }
}