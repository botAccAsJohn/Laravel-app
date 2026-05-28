<?php

use App\Http\Controllers\{ProfileController, Product2Controller, CacheMonitorController, CartController, OrderController, RecentlyViewController, ReviewController, LocaleController, NotificationController};
use App\Http\Controllers\Admin\{ReportManagerController, SalesAnalyticsController, AuthController};
use App\Http\Controllers\Auth\ManualAuthController;
use App\Services\CacheMonitorService;
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

Route::middleware(['auth:admin'])->group(function () {
    Route::resource('products', Product2Controller::class)->except(['index', 'show']);

    // Cache Monitor (admin only)
    Route::get('/admin/cache', [CacheMonitorController::class, 'index'])->name('admin.cache.index');
    Route::post('/admin/cache/clear', [CacheMonitorController::class, 'clear'])->name('admin.cache.clear');

    // Sales Analytics (admin only)
    Route::get('/admin/analytics', [SalesAnalyticsController::class, 'index'])->name('admin.analytics.index');
    Route::get('/admin/analytics/export', [SalesAnalyticsController::class, 'export'])->name('admin.analytics.export');

    // Reports Manager (admin only)
    Route::get('/admin/reports', [ReportManagerController::class, 'index'])->name('admin.reports.index');
    Route::post('/admin/reports/archive', [ReportManagerController::class, 'archive'])->name('admin.reports.archive');
    Route::post('/admin/reports/cleanup', [ReportManagerController::class, 'bulkCleanup'])->name('admin.reports.cleanup');

    // Admin Alerts
    Route::get('/admin/alerts', [\App\Http\Controllers\Admin\AdminAlertController::class, 'index'])->name('admin.alerts.index');
    Route::post('/admin/alerts', [\App\Http\Controllers\Admin\AdminAlertController::class, 'store'])->name('admin.alerts.store');

    // Product Import (Exercise 46.3)
    Route::get('/admin/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'showForm'])->name('admin.import.form');
    Route::post('/admin/import', [\App\Http\Controllers\Admin\ProductImportController::class, 'import'])->name('admin.import.process');
    Route::get('/admin/import/queued/{batchCacheKey}/poll', [\App\Http\Controllers\Admin\ProductImportController::class, 'pollBatchId'])->name('admin.import.poll');
    Route::get('/admin/import/progress/{batchId}', [\App\Http\Controllers\Admin\ProductImportController::class, 'progress'])->name('admin.import.progress');
    Route::get('/admin/import/progress/{batchId}/status', [\App\Http\Controllers\Admin\ProductImportController::class, 'getProgress'])->name('admin.import.progress.status');
    Route::post('/admin/import/progress/{batchId}/cancel', [\App\Http\Controllers\Admin\ProductImportController::class, 'cancel'])->name('admin.import.cancel');
});

Route::middleware(['auth:web,admin'])->group(function () {
    Route::resource('products', Product2Controller::class)->only(['index', 'show']);

    // Order Routes (Shared)
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/orders/analytics', [OrderController::class, 'analytics'])->name('orders.analytics');
    Route::get('/invoices/{order}/download', [OrderController::class, 'invoice'])->name('invoices.download');
    Route::get('/orders', [OrderController::class, 'index'])->middleware('verified')->name('orders.index');
    Route::resource('orders', OrderController::class)->except(['index', 'create', 'store']);
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

    // Coupon Routes
    Route::post('/orders/coupon/validate', [OrderController::class, 'validateCoupon'])->name('coupon.validate');
    Route::post('/orders/coupon/remove', [OrderController::class, 'removeCoupon'])->name('coupon.remove');

    // Recently Viewed Routes
    Route::get('/recently-viewed', [RecentlyViewController::class, 'index'])->name('recently.index');
    Route::post('/recently-viewed/clear', [RecentlyViewController::class, 'clear'])->name('recently.clear');

    Route::get('/orders/create', [OrderController::class, 'create'])->middleware('verified')->name('orders.create');
    Route::post('/orders', [OrderController::class, 'store'])->middleware(['throttle:checkout', 'verified'])->name('orders.store');

    // Logs Route
    Route::get('/logs', [Product2Controller::class, 'logs'])->name('logs.index');

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

Route::get('/export-products', [Product2Controller::class, 'exportProducts'])->name('products.export');


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
    Route::get('register',  [ManualAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [ManualAuthController::class, 'register'])->name('register.store');

    Route::get('login',  [ManualAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [ManualAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');

    // Magic link request form + generator
    Route::post('magic-link', [ManualAuthController::class, 'magicLinkGenerate'])->name('magic.generate');
});

// Authenticated customer: logout (own session)
Route::middleware('auth:web')->prefix('manual-auth')->name('manual-auth.')->group(function () {
    Route::post('logout', [ManualAuthController::class, 'logout'])->name('logout');
});

// Signed magic-link login (no auth required — the signed URL is the proof)
Route::get('manual-auth/magic-login/{userId}', [ManualAuthController::class, 'magicLinkLogin'])
    ->name('manual-auth.magic.login');

