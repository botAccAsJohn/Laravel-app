<?php

use App\Models\User;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

it('sets locale from query parameter and persists to session', function () {
    $this->get('/?lang=hi');

    expect(App::getLocale())->toBe('hi');
    expect(Session::get('locale'))->toBe('hi');
});

it('saves preferred locale to logged in user from query parameter', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/?lang=ar');

    expect(App::getLocale())->toBe('ar');
    expect($user->fresh()->preferred_locale)->toBe('ar');
});

it('falls back to user preferred locale if no session exists', function () {
    $user = User::factory()->create(['preferred_locale' => 'hi']);

    // Ensure session doesn't contain locale from previous runs
    Session::forget('locale');

    $this->actingAs($user)->get('/');

    expect(App::getLocale())->toBe('hi');
});

it('falls back to default config locale if nothing else is set', function () {
    config(['app.locale' => 'en']);
    Session::forget('locale');

    $this->get('/');

    expect(App::getLocale())->toBe('en');
});
