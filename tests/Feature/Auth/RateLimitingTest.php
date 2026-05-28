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

    // Verify rate limit is clear initially
    expect(RateLimiter::remaining('login:'.$email.'|'.$ip, 5))->toBe(5);

    // Send 5 failed login attempts
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'wrong-password',
        ]);
        
        $response->assertStatus(302); // Redirect back
        $response->assertSessionHasErrors('email');
    }

    // The 6th attempt should be blocked and rate-limited
    $response = $this->post('/login', [
        'email' => $email,
        'password' => 'wrong-password',
    ]);

    // Check we got redirected back with throttle error in session
    $response->assertStatus(302);
    $response->assertSessionHasErrors('email');
    
    // Check that we hit the rate limit (0 remaining)
    expect(RateLimiter::remaining('login:'.$email.'|'.$ip, 5))->toBe(0);

    // Confirm that the hit was logged to security channel
    $logPath = storage_path('logs/security.log');
    expect(File::exists($logPath))->toBeTrue();
    $logContent = File::get($logPath);
    expect($logContent)->toContain('Rate limit hit: login');
    expect($logContent)->toContain($email);
    expect($logContent)->toContain($ip);
});

test('password reset is throttled after 3 attempts per email', function () {
    $email = 'test@example.com';

    // Verify rate limit is clear initially
    expect(RateLimiter::remaining('password-reset:'.$email, 3))->toBe(3);

    // Send 3 requests
    for ($i = 0; $i < 3; $i++) {
        $response = $this->post('/forgot-password', [
            'email' => $email,
        ]);
        $response->assertStatus(302); // Should redirect back (email not found or mail sent)
    }

    // The 4th attempt should be rate-limited
    $response = $this->post('/forgot-password', [
        'email' => $email,
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
