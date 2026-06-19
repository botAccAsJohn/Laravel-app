<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    public function test_login_throttled_after_six_attempts()
    {
        Cache::flush();

        $email = 'throttle-test@example.com';

        $logFile = storage_path('logs/security.log');
        if (file_exists($logFile)) {
            @unlink($logFile);
        }

        for ($i = 1; $i <= 5; $i++) {
            $response = $this->postJson('/login', [
                'email' => $email,
                'password' => 'incorrect-password',
            ]);
            $this->assertNotEquals(429, $response->status(), "Attempt {$i} unexpectedly rate-limited");
        }

        $sixth = $this->postJson('/login', [
            'email' => $email,
            'password' => 'incorrect-password',
        ]);

        $sixth->assertStatus(429);

        $this->assertFileExists($logFile);
        $this->assertStringContainsString('Rate limit hit: login', file_get_contents($logFile));
    }

    public function test_password_reset_throttled_after_four_attempts()
    {
        Cache::flush();

        $email = 'password-reset-test@example.com';

        $logFile = storage_path('logs/security.log');
        if (file_exists($logFile)) {
            @unlink($logFile);
        }

        for ($i = 1; $i <= 3; $i++) {
            $response = $this->postJson('/forgot-password', [
                'email' => $email,
            ]);
            $this->assertNotEquals(429, $response->status(), "Attempt {$i} unexpectedly rate-limited");
        }

        $fourth = $this->postJson('/forgot-password', [
            'email' => $email,
        ]);

        $fourth->assertStatus(429);

        $this->assertFileExists($logFile);
        $this->assertStringContainsString('Rate limit hit: password-reset', file_get_contents($logFile));
    }
}
