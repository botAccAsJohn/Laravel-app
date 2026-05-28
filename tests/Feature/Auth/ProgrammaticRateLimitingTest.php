<?php

use App\Models\User;
use App\Models\Product;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    RateLimiter::clear('reviews:1');
    RateLimiter::clear('support-tickets:1');
    RateLimiter::clear('contact-form:127.0.0.1');

    if (File::exists(storage_path('logs/security.log'))) {
        File::put(storage_path('logs/security.log'), '');
    }
});

test('review submission is programmatically rate limited', function () {
    $user = User::factory()->create(['id' => 1]);
    $product = Product::factory()->create();

    // 5 attempts allowed
    for ($i = 0; $i < 5; $i++) {
        $response = $this->actingAs($user)->post(route('reviews.store', $product), [
            'rating' => 5,
            'review_text' => 'Great product!',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
    }

    // 6th attempt should be blocked
    $response = $this->actingAs($user)->post(route('reviews.store', $product), [
        'rating' => 5,
        'review_text' => 'Great product!',
    ]);
    $response->assertStatus(302);
    $response->assertSessionHasErrors('review_text');

    $logContent = File::get(storage_path('logs/security.log'));
    expect($logContent)->toContain('Rate limit hit: reviews submission');
});

test('support ticket submission is programmatically rate limited', function () {
    $user = User::factory()->create(['id' => 1]);

    // 3 attempts allowed
    for ($i = 0; $i < 3; $i++) {
        $response = $this->actingAs($user)->post(route('support-tickets.store'), [
            'subject' => 'Issue ' . $i,
            'description' => 'I have an issue.',
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'priority' => 'medium',
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
    }

    // 4th attempt should be blocked
    $response = $this->actingAs($user)->post(route('support-tickets.store'), [
        'subject' => 'Blocked issue',
        'description' => 'I have an issue.',
        'customer_name' => $user->name,
        'customer_email' => $user->email,
        'priority' => 'medium',
    ]);
    $response->assertStatus(302);
    $response->assertSessionHasErrors('ticket_error');

    $logContent = File::get(storage_path('logs/security.log'));
    expect($logContent)->toContain('Rate limit hit: support ticket submission');
});

test('contact form submission is programmatically rate limited', function () {
    // 5 attempts allowed
    for ($i = 0; $i < 5; $i++) {
        $response = $this->post(route('contact.submit'), [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'message' => 'Hello ' . $i,
        ]);
        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
    }

    // 6th attempt should be blocked
    $response = $this->post(route('contact.submit'), [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'message' => 'Hello blocked',
    ]);
    $response->assertStatus(302);
    $response->assertSessionHasErrors('contact_error');

    $logContent = File::get(storage_path('logs/security.log'));
    expect($logContent)->toContain('Rate limit hit: contact form submission');
});
