<?php

use App\Models\User;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use App\Http\Middleware\ReqContextMiddleware;

it('adds request details to Context for guest user', function () {
    Context::flush();

    $request = Request::create('/', 'GET');
    $request->server->set('REMOTE_ADDR', '192.168.1.1');

    $middleware = new ReqContextMiddleware();
    
    Log::shouldReceive('channel')
        ->with('user')
        ->once()
        ->andReturnSelf()
        ->shouldReceive('info')
        ->with('user performed an action')
        ->once();

    $response = $middleware->handle($request, function ($req) {
        expect(Context::get('request_id'))->not->toBeNull();
        expect(Context::get('user_id'))->toBeNull();
        expect(Context::get('user_type'))->toBe('user');
        expect(Context::get('ip_address'))->toBe('192.168.1.1');

        return response('OK');
    });

    expect($response->getContent())->toBe('OK');
});

it('adds authenticated user details to Context', function () {
    Context::flush();

    $user = User::factory()->make(['id' => 456, 'role' => 'customer']);
    $request = Request::create('/', 'GET');
    $request->setUserResolver(fn() => $user);

    $middleware = new ReqContextMiddleware();

    Log::shouldReceive('channel')
        ->with('customer')
        ->once()
        ->andReturnSelf()
        ->shouldReceive('info')
        ->with('customer performed an action')
        ->once();

    $middleware->handle($request, function ($req) use ($user) {
        expect(Context::get('user_id'))->toBe(456);
        expect(Context::get('user_type'))->toBe('customer');
        return response('OK');
    });
});

it('adds admin details to Context when using admin guard', function () {
    Context::flush();

    $admin = new Admin();
    $admin->id = 789;
    $admin->name = 'Admin User';
    $admin->email = 'admin@test.com';

    // Log the admin in to the admin guard
    Auth::guard('admin')->setUser($admin);

    $request = Request::create('/', 'GET');

    $middleware = new ReqContextMiddleware();

    Log::shouldReceive('channel')
        ->with('admin')
        ->once()
        ->andReturnSelf()
        ->shouldReceive('info')
        ->with('admin performed an action')
        ->once();

    $middleware->handle($request, function ($req) use ($admin) {
        expect(Context::get('user_id'))->toBe(789);
        expect(Context::get('user_type'))->toBe('admin');
        return response('OK');
    });

    // Clean up
    Auth::guard('admin')->logout();
});
