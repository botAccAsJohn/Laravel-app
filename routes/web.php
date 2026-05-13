<?php

use App\Http\Controllers\{ProfileController, Product2Controller, CacheMonitorController, CartController, OrderController, RecentlyViewController, ReviewController, LocaleController, NotificationController};
use App\Http\Controllers\Admin\{ReportManagerController, SalesAnalyticsController};
use App\Services\CacheMonitorService;
use Illuminate\Support\Facades\{Route, Auth};

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::get('/dashboard', function (CacheMonitorService $monitor) {
    if (Auth::user()->role !== 'admin') {
        return redirect()->route('products.index');
    }
    $stats = $monitor->stats();
    return view('dashboard', compact('stats'));
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:admin'])->group(function () {
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
    });
    Route::resource('products', Product2Controller::class)->only(['index', 'show']);

    // Cart Routes
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add/{productId}', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/decrement/{productId}', [CartController::class, 'decrement'])->name('cart.decrement');
    Route::post('/cart/remove/{productId}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    // Order Cancel Route
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    // Coupon Routes
    Route::post('/orders/coupon/validate', [OrderController::class, 'validateCoupon'])->name('coupon.validate');
    Route::post('/orders/coupon/remove', [OrderController::class, 'removeCoupon'])->name('coupon.remove');

    // Recently Viewed Routes
    Route::get('/recently-viewed', [RecentlyViewController::class, 'index'])->name('recently.index');
    Route::post('/recently-viewed/clear', [RecentlyViewController::class, 'clear'])->name('recently.clear');

    // Order History Analysis
    Route::get('/orders/analytics', [OrderController::class, 'analytics'])->name('orders.analytics');
    Route::get('/invoices/{order}/download', [OrderController::class, 'invoice'])->name('invoices.download');
    Route::resource('orders', OrderController::class);

    // Logs Route
    Route::get('/logs', [Product2Controller::class, 'logs'])->name('logs.index');

    // Reviews Route
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');

    // Notifications Routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'update'])->name('notifications.markAsRead');
});


Route::get('/export-products', [Product2Controller::class, 'exportProducts'])->name('products.export');


// Route::get('/test-slack', function () {
//     $order = \App\Models\Order::find(2);
//     dump($order);
//     \Illuminate\Support\Facades\Notification::send(\App\Models\User::find(2), new \App\Notifications\NewOrderReceived($order));
//     dump("here !!");
//     return "Notification sent successfully.....ssd";
// });
require __DIR__ . '/auth.php';
