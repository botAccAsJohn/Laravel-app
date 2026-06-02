<?php

use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clear rate limits before each test
    RateLimiter::clear('login:|127.0.0.1');
    RateLimiter::clear('login:test@example.com|127.0.0.1');
    RateLimiter::clear('password-reset:test@example.com');
    RateLimiter::clear('api:127.0.0.1');

    // Clean security log if exists
    if (File::exists(storage_path('logs/security.log'))) {
        File::put(storage_path('logs/security.log'), '');
    }
});

test('login is throttled after 5 attempts and logs to security channel', function () {
    $email = 'test@example.com';
    $ip = '127.0.0.1';

    // Clear any existing cache for this email
    \Illuminate\Support\Facades\Cache::forget('login_failures:' . strtolower($email));

    // Send 5 failed login attempts
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302); // Redirect back
        $response->assertSessionHasErrors('email');
    }

    // Get the service to check failure count
    $service = app(\App\Services\LoginThrottleService::class);

    // After 5 failed attempts, the account counter should be at 5
    expect($service->failureCount($email))->toBe(5);

    // The 6th attempt should be blocked by the HTTP rate limiter middleware (429)
    $response = $this->post('/login', [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    // Expect a 429 Too Many Requests (rate limit exceeded)
    expect($response->getStatusCode())->toBe(429);

    // The failure count should still be 5 because the middleware blocked the request
    expect($service->failureCount($email))->toBe(5);

    // Confirm that the failures were logged to security channel
    ]);

    $response->assertStatus(302);
    $response->assertSessionHasErrors('email');

    // Check security log for hit
    $logPath = storage_path('logs/security.log');
    expect(File::exists($logPath))->toBeTrue();
    $logContent = File::get($logPath);
    expect($logContent)->toContain('Rate limit hit: password-reset');
    expect($logContent)->toContain($email);
});
