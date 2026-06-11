<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

test('gates are defined and restrict normal users', function () {
    $user = User::factory()->create(['role' => 'customer']);

    expect(Gate::forUser($user)->allows('view_admin_dashboard'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage_products'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage_orders'))->toBeFalse();
    expect(Gate::forUser($user)->allows('impersonate_users'))->toBeFalse();
    expect(Gate::forUser($user)->allows('view_analytics'))->toBeFalse();
});

test('admins have access to admin gates', function () {
    $admin = Admin::create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => bcrypt('password'),
    ]);

    expect(Gate::forUser($admin)->allows('view_admin_dashboard'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manage_products'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manage_orders'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('impersonate_users'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('view_analytics'))->toBeTrue();
});

test('users with admin role have access to admin gates', function () {
    $user = User::factory()->create(['role' => 'admin']);

    expect(Gate::forUser($user)->allows('view_admin_dashboard'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage_products'))->toBeTrue();
    expect(Gate::forUser($user)->allows('manage_orders'))->toBeTrue();
    expect(Gate::forUser($user)->allows('impersonate_users'))->toBeTrue();
    expect(Gate::forUser($user)->allows('view_analytics'))->toBeTrue();
});

test('super-admin bypass works via Gate::before', function () {
    config(['auth.super_admin_emails' => 'super@example.com']);

    $superAdmin = Admin::create([
        'name' => 'Super Admin',
        'email' => 'super@example.com',
        'password' => bcrypt('password'),
    ]);
    $regularAdmin = Admin::create([
        'name' => 'Regular Admin',
        'email' => 'regular@example.com',
        'password' => bcrypt('password'),
    ]);

    $this->actingAs($superAdmin, 'admin');

    expect(Gate::allows('some-undefined-gate'))->toBeTrue();
    expect(Gate::forUser($superAdmin)->allows('some-undefined-gate'))->toBeTrue();

    $this->actingAs($regularAdmin, 'admin');
    expect(Gate::allows('some-undefined-gate'))->toBeFalse();
});

test('super-admin bypass does not apply when impersonating', function () {
    config(['auth.super_admin_emails' => 'super@example.com']);

    $superAdmin = Admin::create([
        'name' => 'Super Admin',
        'email' => 'super@example.com',
        'password' => bcrypt('password'),
    ]);
    
    session(['impersonator_id' => $superAdmin->id]);

    $this->actingAs($superAdmin, 'admin');

    expect(Gate::allows('some-undefined-gate'))->toBeFalse();
    
    session()->forget('impersonator_id');
});

test('authorization decisions are logged via Gate::after', function () {
    Log::shouldReceive('channel')
        ->with('security')
        ->andReturnSelf()
        ->shouldReceive('info')
        ->with('[Gate::after] Authorization decision', Mockery::on(function ($data) {
            return $data['ability'] === 'manage_products';
        }))
        ->once();

    $user = User::factory()->create(['role' => 'customer']);

    Gate::forUser($user)->allows('manage_products');
});
