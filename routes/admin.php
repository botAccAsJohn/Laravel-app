<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{
    AuthController,
    UserRoleController,
    ReportManagerController,
    SalesAnalyticsController,
    AdminAlertController,
    ProductImportController,
    ForcePasswordResetController,
    ImpersonationController,
};
use App\Http\Controllers\Auth\ManualAuthController;
use App\Http\Controllers\Product2Controller;
use App\Http\Controllers\CacheMonitorController;

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
|
| All routes in this file are automatically wrapped with the `web`
| middleware group, prefixed with `/admin`, and named with the `admin.`
| prefix by bootstrap/app.php — no need to repeat those here.
|
*/

// ── Unauthenticated / Guest Routes ──────────────────────────────────────
// `guest:admin` checks the ADMIN guard specifically. Without the guard
// argument, Laravel would fall back to the default `web` guard, meaning
// an authenticated admin would not be redirected away from the login page.
Route::middleware('guest:admin')->group(function () {

    Route::get('login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('login', [AuthController::class, 'login'])
        ->name('login.store')
        ->middleware('throttle:login');

});

// ── Authenticated Admin Routes ───────────────────────────────────────────
Route::middleware(['auth:admin', 'verified'])->group(function () {

    Route::get('dashboard', [AuthController::class, 'dashboard'])
        ->name('dashboard');

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');

    // ── Impersonation (→ ImpersonationController) ─────────────────────────
    Route::get('impersonate',           [ImpersonationController::class, 'index'])->name('impersonate.index');
    Route::post('impersonate/{user}',    [ImpersonationController::class, 'impersonate'])->name('impersonate.start');
    Route::post('impersonate/stop',      [ImpersonationController::class, 'stopImpersonating'])->name('impersonate.stop');

    // ── Exercise 50.4: User Role Management ───────────────────────────────────────
    Route::get('users',                         [UserRoleController::class, 'index'])->name('users.index');
    Route::get('users/{user}/roles',            [UserRoleController::class, 'edit'])->name('users.edit-roles');
    Route::patch('users/{user}/roles',          [UserRoleController::class, 'update'])->name('users.update-roles');
    Route::post('users/{user}/roles/assign',    [UserRoleController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('users/{user}/roles/revoke',  [UserRoleController::class, 'revokeRole'])->name('users.revoke-role');
    Route::post('users/{user}/magic-link',      [UserRoleController::class, 'generateMagicLink'])->name('users.magic-link');

    // Exercise 52.3 — Force a mandatory password reset on a user account.
    // The admin POSTs with an optional `reason` field.
    Route::post('users/{user}/force-reset', ForcePasswordResetController::class)->name('users.force-reset');

    // ── Product Management (CRUD — create/edit/update/delete) ────────────
    Route::get('products/export', [Product2Controller::class, 'exportProducts'])->name('products.export');
    Route::resource('products', Product2Controller::class)->except(['index', 'show']);

    // Logs Route
    Route::get('/logs', [Product2Controller::class, 'logs'])->name('logs.index');

    // ── Cache Monitor ────────────────────────────────────────────────────
    Route::get('cache',       [CacheMonitorController::class, 'index'])->name('cache.index');
    Route::post('cache/clear',[CacheMonitorController::class, 'clear'])->name('cache.clear');

    // ── Sales Analytics ──────────────────────────────────────────────────
    Route::get('analytics',        [SalesAnalyticsController::class, 'index'])->name('analytics.index');
    Route::get('analytics/export', [SalesAnalyticsController::class, 'export'])->name('analytics.export');

    // ── Reports Manager ──────────────────────────────────────────────────
    Route::get('reports',           [ReportManagerController::class, 'index'])->name('reports.index');
    Route::post('reports/archive',  [ReportManagerController::class, 'archive'])->name('reports.archive');
    Route::post('reports/cleanup',  [ReportManagerController::class, 'bulkCleanup'])->name('reports.cleanup');

    // ── Admin Alerts ─────────────────────────────────────────────────────
    Route::get('alerts',  [AdminAlertController::class, 'index'])->name('alerts.index');
    Route::post('alerts', [AdminAlertController::class, 'store'])->name('alerts.store');

    // ── Product Import (Exercise 46.3) ───────────────────────────────────
    Route::get('import',                                  [ProductImportController::class, 'showForm'])->name('import.form');
    Route::post('import',                                 [ProductImportController::class, 'import'])->name('import.process');
    Route::get('import/queued/{batchCacheKey}/poll',      [ProductImportController::class, 'pollBatchId'])->name('import.poll');
    Route::get('import/progress/{batchId}',               [ProductImportController::class, 'progress'])->name('import.progress');
    Route::get('import/progress/{batchId}/status',        [ProductImportController::class, 'getProgress'])->name('import.progress.status');
    Route::post('import/progress/{batchId}/cancel',       [ProductImportController::class, 'cancel'])->name('import.cancel');

});
