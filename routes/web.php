<?php

use App\Http\Controllers\{ProfileController, ProductController, CacheMonitorController, CartController, OrderController, RecentlyViewController, ReviewController, LocaleController, NotificationController, DeviceController, SupportTicketController, ContactController, DashboardController};
use App\Http\Controllers\Auth\ManualAuthController;
use App\Http\Controllers\Admin\{
    AuthController,
    UserRoleController,
    RoleController,
    ReportManagerController,
    SalesAnalyticsController,
    AdminAlertController,
    ProductImportController,
    ForcePasswordResetController,
    ImpersonationController,
};
use Illuminate\Support\Facades\Route;


// ═══════════════════════════════════════════════════════════════════════════
//  PUBLIC ROUTES (no auth required)
// ═══════════════════════════════════════════════════════════════════════════

Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

Route::redirect('/', '/products');

Route::get('/dashboard', DashboardController::class)->name('dashboard');

// ── Contact (public) ─────────────────────────────────────────────────────
Route::get('/contact', [ContactController::class, 'create'])->name('contact.index');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// ── Support Tickets (public) ─────────────────────────────────────────────
Route::prefix('support')->name('support.')->group(function () {
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])->name('tickets.store');
});

// ── Products (public) ────────────────────────────────────────────────────
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');


// ═══════════════════════════════════════════════════════════════════════════
//  AUTHENTICATED ROUTES (any logged-in user — admin or customer)
// ═══════════════════════════════════════════════════════════════════════════

// ── Profile (any authenticated user) ────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->middleware('verified')->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::delete('/profile/other-devices', [ProfileController::class, 'logoutOtherDevices'])
        ->name('profile.logout-other-devices');

    // Notification Routes ────────────────────────────────────────────────
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
    Route::get('/notifications/{id}', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'update'])->name('notifications.markAsRead');
});

// ═══════════════════════════════════════════════════════════════════════════
//  CUSTOMER ROUTES (role:customer)
// ═══════════════════════════════════════════════════════════════════════════

// ── Verified Customer and Admin Routes ────────────────────────────────
Route::middleware(['auth:web,admin', 'verified'])->group(function () {

    // Order Routes ─────────────────────────────────────────────────────
    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/create', [OrderController::class, 'create'])->name('orders.create');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders', [OrderController::class, 'store'])->middleware(['throttle:checkout'])->name('orders.store');
    Route::post('/orders/coupon/validate', [OrderController::class, 'validateCoupon'])->name('coupon.validate');
    Route::post('/orders/coupon/remove', [OrderController::class, 'removeCoupon'])->name('coupon.remove');
    Route::post('/orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
    Route::get('/invoices/{order}/download', [OrderController::class, 'invoice'])->name('invoices.download');
    Route::get('/orders/{order}/edit', [OrderController::class, 'edit'])->name('orders.edit');
    Route::patch('/orders/{order}', [OrderController::class, 'update'])->name('orders.update');
    Route::delete('/orders/{order}', [OrderController::class, 'destroy'])->name('orders.destroy');

    // Review Routes ──────────────────────────────────────────────────
    Route::post('/products/{product}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::patch('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // ── Sales Analytics (permission: view_analytics) ─────────────────
    Route::middleware(['permission:view_analytics'])->group(function () {
        Route::get('analytics', [SalesAnalyticsController::class, 'index'])->name('analytics.index');
        Route::get('analytics/export', [SalesAnalyticsController::class, 'export'])->name('analytics.export');
    });
});

Route::middleware(['auth:web'])->group(function () {
    Route::get('/profile/devices', [DeviceController::class, 'index'])->name('devices.index');
    Route::delete('/profile/devices/{id}', [DeviceController::class, 'revoke'])->name('devices.revoke');
    Route::delete('/profile/devices', [DeviceController::class, 'revokeAll'])->name('devices.revokeAll');
});

// ── Guest or Customer ────────────────────────────────────────────────────
Route::middleware('guest_or_customer')->group(function () {
    Route::get('/recently-viewed', [RecentlyViewController::class, 'index'])->name('recently.index');
    Route::post('/recently-viewed/clear', [RecentlyViewController::class, 'clear'])->name('recently.clear');
});


// ═══════════════════════════════════════════════════════════════════════════
//  AUTH SCAFFOLDING
// ═══════════════════════════════════════════════════════════════════════════
require __DIR__ . '/auth.php';
require __DIR__ . '/cart.php';

// ── Manual Authentication (customer) — Guest only ───────────────────────
Route::middleware('guest')->prefix('manual-auth')->name('manual-auth.')->group(function () {
    Route::get('register', [ManualAuthController::class, 'showRegister'])->name('register');
    Route::post('register', [ManualAuthController::class, 'register'])->name('register.store');

    Route::get('login', [ManualAuthController::class, 'showLogin'])->name('login');
    Route::post('login', [ManualAuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('login.store');
});

// ── Manual Authentication (customer) — Logout ───────────────────────────
Route::middleware('auth:web')->prefix('manual-auth')->name('manual-auth.')->group(function () {
    Route::post('logout', [ManualAuthController::class, 'logout'])->name('logout');
});

// ── Signed magic-link login ──────────────────────────────────────────────
Route::get('magic-login/{userId}', [ManualAuthController::class, 'magicLinkLogin'])->name('magic.login');


// ═══════════════════════════════════════════════════════════════════════════
//  ADMIN ROUTES (permission-based access control)
// ═══════════════════════════════════════════════════════════════════════════

// ── Admin Guest (login page — only accessible when NOT logged in as admin)
Route::middleware('guest:admin')->prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store')
        ->middleware('throttle:login');
});

// ── Product Management (permission: manage_products)
Route::middleware(['auth:admin,web', 'permission:manage_products'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('products/export', [ProductController::class, 'exportProducts'])->name('products.export');
        Route::resource('products', ProductController::class)->except(['index', 'show']);
    });

// ── Authenticated Admin ──────────────────────────────────────────────────
Route::middleware(['auth:admin,web'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        // ── Dashboard & Logout (all admins) ──────────────────────────────
        Route::get('dashboard', [AuthController::class, 'dashboard'])->name('dashboard');
        Route::post('logout', [AuthController::class, 'logout'])->name('logout');

        // ── Product Import (permission: import_products) ─────────────────
        Route::middleware(['permission:import_products'])->group(function () {
            Route::get('import', [ProductImportController::class, 'showForm'])->name('import.form');
            Route::post('import', [ProductImportController::class, 'import'])->name('import.process');
            Route::get('import/queued/{batchCacheKey}/poll', [ProductImportController::class, 'pollBatchId'])->name('import.poll');
            Route::get('import/progress/{batchId}', [ProductImportController::class, 'progress'])->name('import.progress');
            Route::get('import/progress/{batchId}/status', [ProductImportController::class, 'getProgress'])->name('import.progress.status');
            Route::post('import/progress/{batchId}/cancel', [ProductImportController::class, 'cancel'])->name('import.cancel');
        });

        // ── Activity Logs (permission: view_logs) ────────────────────────
        Route::middleware(['permission:view_logs'])->group(function () {
            Route::get('/logs', [ProductController::class, 'logs'])->name('logs.index');
        });

        // ── Cache Monitor (permission: manage_products) ──────────────────
        Route::middleware(['permission:manage_products'])->group(function () {
            Route::get('cache', [CacheMonitorController::class, 'index'])->name('cache.index');
            Route::post('cache/clear', [CacheMonitorController::class, 'clear'])->name('cache.clear');
        });

        
        // ── Reports Manager (permission: manage_reports) ─────────────────
        Route::middleware(['permission:manage_reports'])->group(function () {
            Route::get('reports', [ReportManagerController::class, 'index'])->name('reports.index');
            Route::post('reports/archive', [ReportManagerController::class, 'archive'])->name('reports.archive');
            Route::post('reports/cleanup', [ReportManagerController::class, 'bulkCleanup'])->name('reports.cleanup');
        });

        // ── Admin Alerts (permission: send_alerts) ───────────────────────
        Route::middleware(['permission:send_alerts'])->group(function () {
            Route::get('alerts', [AdminAlertController::class, 'index'])->name('alerts.index');
            Route::post('alerts', [AdminAlertController::class, 'store'])->name('alerts.store');
        });

        // ── Impersonation ──────────────────────────────────────────────────
        Route::post('impersonate/stop', [ImpersonationController::class, 'stopImpersonating'])->name('impersonate.stop');

        Route::middleware(['permission:impersonate_users'])->group(function () {
            Route::get('impersonate', [ImpersonationController::class, 'index'])->name('impersonate.index');
            Route::post('impersonate/{user}', [ImpersonationController::class, 'impersonate'])->name('impersonate.start');
        });

        // ── User & Role Management (permission: manage_users) ────────────
        Route::middleware(['permission:manage_users'])->group(function () {
            Route::get('users', [UserRoleController::class, 'index'])->name('users.index');
            Route::get('users/{user}/roles', [UserRoleController::class, 'edit'])->name('users.edit-roles');
            Route::patch('users/{user}/roles', [UserRoleController::class, 'update'])->name('users.update-roles');
            Route::post('users/{user}/roles/assign', [UserRoleController::class, 'assignRole'])->name('users.assign-role');
            Route::delete('users/{user}/roles/revoke', [UserRoleController::class, 'revokeRole'])->name('users.revoke-role');
            Route::post('users/{user}/magic-link', [UserRoleController::class, 'generateMagicLink'])->name('users.magic-link');

            // Role CRUD
            Route::resource('roles', RoleController::class)->except(['show']);
            Route::post('users/{user}/force-reset', ForcePasswordResetController::class)->name('users.force-reset');
        });
    });
    
