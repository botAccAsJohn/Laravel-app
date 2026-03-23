<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AIService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-3-flash-preview:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key', env('GEMINI_API_KEY'));
    }

    /**
     * Send a single prompt and return the text response.
     */
    public function ask(string $prompt, ?int $userId = null): string
    {
        $response = Http::post("{$this->apiUrl}?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ]);

        if ($response->failed() || $response->json('error')) {
            $errorMsg = $response->json('error.message', 'Unknown API error');
            $errorCode = $response->json('error.code', $response->status());
            return "API Error [{$errorCode}]: {$errorMsg}";
        }

        $text = $response->json('candidates.0.content.parts.0.text', 'No response from AI.');

        // Optionally store in cache keyed by user
        if ($userId) {
            $history = Cache::get("ai_history_{$userId}", []);
            $history[] = ['role' => 'user', 'prompt' => $prompt, 'response' => $text, 'at' => now()->toDateTimeString()];
            Cache::put("ai_history_{$userId}", array_slice($history, -50), now()->addDays(7));
        }

        return $text;
    }

    /**
     * Send a prompt with prior conversation history.
     */
    public function askWithHistory(string $prompt, array $history = [], ?int $userId = null): string
    {
        $contents = [];

        foreach ($history as $turn) {
            $contents[] = [
                'role'  => $turn['role'],         // 'user' or 'model'
                'parts' => [['text' => $turn['text']]],
            ];
        }

        // Add the new user message
        $contents[] = [
            'role'  => 'user',
            'parts' => [['text' => $prompt]],
        ];

        $response = Http::post("{$this->apiUrl}?key={$this->apiKey}", [
            'contents' => $contents,
        ]);

        return $response->json('candidates.0.content.parts.0.text', 'No response from AI.');
    }

    /**
     * Retrieve stored prompt history for a user.
     */
    public function getHistory(?int $userId, int $limit = 50): array
    {
        if (!$userId) {
            return [];
        }

        $history = Cache::get("ai_history_{$userId}", []);

        return array_slice(array_reverse($history), 0, $limit);
    }
}
