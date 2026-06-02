<?php

use App\Http\Controllers\{ProfileController, Product2Controller, CacheMonitorController, CartController, OrderController, RecentlyViewController, ReviewController, LocaleController, NotificationController};
use App\Http\Controllers\Auth\ManualAuthController;
use Illuminate\Support\Facades\{Route, Auth};

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

// Admin routes are defined in routes/admin.php (loaded by bootstrap/app.php)

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function () {
    if (Auth::guard('admin')->check()) {
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('products.index');
})->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('verified')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Exercise 49.5 — log out all other devices / sessions
    Route::delete('/profile/other-devices', [ProfileController::class, 'logoutOtherDevices'])
        ->name('profile.logout-other-devices');
});

Route::middleware(['auth:web,admin', 'verified'])->group(function () {
    Route::resource('products', Product2Controller::class)->only(['index', 'show']);

    // ── Order Routes (Shared — accessible by both web and admin guards) ─────────
    // IMPORTANT: Static segments (/orders/create, /orders/analytics) MUST be
    // registered BEFORE Route::resource() which generates the wildcard pattern
    // GET /orders/{order}. Laravel matches routes top-to-bottom; if the resource
    // is registered first, "create" and "analytics" are treated as {order} IDs,
    // the model binding fails, and a 404 is returned before reaching the controller.

    // Static named routes first ─────────────────────────────────────────────────
    Route::get('/orders', [OrderController::class, 'index'])->middleware('verified')->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->middleware('verified')->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->middleware(['throttle:checkout', 'verified'])->name('orders.store');
    Route::get('/orders/analytics', [OrderController::class, 'analytics'])->name('orders.analytics');
    Route::post('/orders/coupon/validate', [OrderController::class, 'validateCoupon'])->name('coupon.validate');
    Route::post('/orders/coupon/remove', [OrderController::class, 'removeCoupon'])->name('coupon.remove');

    // Wildcard resource routes after all static segments ────────────────────────
    Route::resource('orders', OrderController::class)->except(['index', 'create', 'store']);
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/invoices/{order}/download', [OrderController::class, 'invoice'])->name('invoices.download');
});

Route::middleware(['auth:web'])->group(function () {
    // ── Device / Token Management (Exercise 49.2) ───────────
    Route::get('/profile/devices', [\App\Http\Controllers\DeviceController::class, 'index'])->name('devices.index');
    Route::delete('/profile/devices/{id}', [\App\Http\Controllers\DeviceController::class, 'revoke'])->name('devices.revoke');
    Route::delete('/profile/devices', [\App\Http\Controllers\DeviceController::class, 'revokeAll'])->name('devices.revokeAll');

    // Cart Routes
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{productId}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/decrement/{productId}', [CartController::class, 'decrement'])->name('cart.decrement');
    Route::post('/cart/remove/{productId}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    // Coupon Routes are in the auth:web,admin group above (alongside all other order routes)

    // Recently Viewed Routes
    Route::get('/recently-viewed', [RecentlyViewController::class, 'index'])->name('recently.index');
    Route::post('/recently-viewed/clear', [RecentlyViewController::class, 'clear'])->name('recently.clear');

    // orders.create and orders.store moved to auth:web,admin group above
    // so they work for both web users and admin-guard users.

    // Reviews Routes (Exercise 50.2 — ReviewPolicy enforces 24h edit window)
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // Notifications Routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'update'])->name('notifications.markAsRead');

    // Support Ticket Submission (Exercise 47.3)
    Route::post('/support-tickets', [\App\Http\Controllers\SupportTicketController::class, 'store'])->name('support-tickets.store');
});

Route::post('/contact', [\App\Http\Controllers\ContactController::class, 'submit'])->name('contact.submit');



// Route::get('/test-slack', function () {
//     $order = \App\Models\Order::find(2);
//     dump($order);
//     \Illuminate\Support\Facades\Notification::send(\App\Models\User::find(2), new \App\Notifications\NewOrderReceived($order));
//     dump("here !!");
//     return "Notification sent successfully.....ssd";
// });


require __DIR__ . '/auth.php';

// ── Exercise 49.3 — Manual Authentication Routes ────────────────────────
// Guest-only: custom login & register forms (separate from Breeze routes)
Route::middleware('guest')->prefix('manual-auth')->name('manual-auth.')->group(function () {
    Route::get('register', [ManualAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [ManualAuthController::class, 'register'])->name('register.store');

    Route::get('login', [ManualAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [ManualAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');

});

// Authenticated customer: logout (own session)
Route::middleware('auth:web')->prefix('manual-auth')->name('manual-auth.')->group(function () {
    Route::post('logout', [ManualAuthController::class, 'logout'])->name('logout');
});

// Signed magic-link login (no auth required — the signed URL is the proof)
Route::get('magic-login/{userId}', [ManualAuthController::class, 'magicLinkLogin'])->name('magic.login');

use Illuminate\Foundation\Auth\EmailVerificationRequest;

// Show "please verify" notice
Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

// Handle signed link click
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect('/dashboard?verified=1');
})->middleware(['auth', 'signed'])->name('verification.verify');

// Resend link
Route::post('/email/verification-notification', function (\Illuminate\Http\Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('message', 'Verification link sent!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');