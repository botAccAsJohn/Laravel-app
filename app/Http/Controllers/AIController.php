<?php

namespace App\Http\Controllers;

use App\Services\AIService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AIController extends Controller
{
    public function __construct(protected AIService $aiService) {}

    /**
     * POST /api/ai/ask
     * Send a single prompt to Gemini and return the response.
     */
    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt' => ['required', 'string', 'max:4000'],
        ]);

        $userId = $request->user()?->id;

        $response = $this->aiService->ask($validated['prompt'], $userId);

        return response()->json([
            'prompt'   => $validated['prompt'],
            'response' => $response,
        ]);
    }

    /**
     * POST /api/ai/chat
     * Send a prompt with prior conversation context.
     */
    public function chat(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'prompt'  => ['required', 'string', 'max:4000'],
            'history' => ['sometimes', 'array'],
            'history.*.role' => ['required', 'in:user,model'],
            'history.*.text' => ['required', 'string'],
        ]);

        $userId   = $request->user()?->id;
        $response = $this->aiService->askWithHistory(
            $validated['prompt'],
            $validated['history'] ?? [],
            $userId
        );

        return response()->json([
            'prompt'   => $validated['prompt'],
            'response' => $response,
        ]);
    }
}
