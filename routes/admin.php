<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\{AuthController, UserRoleController};
use App\Http\Controllers\Auth\ManualAuthController;

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
Route::middleware('auth:admin')->group(function () {

    Route::get('dashboard', [AuthController::class, 'dashboard'])
        ->name('dashboard');

    Route::post('logout', [AuthController::class, 'logout'])
        ->name('logout');

    // ── Exercise 49.3: Impersonation (Auth::loginUsingId) ────────────────
    Route::get('impersonate',              [ManualAuthController::class, 'impersonateIndex'])->name('impersonate.index');
    Route::post('impersonate/{userId}',    [ManualAuthController::class, 'impersonateStart'])->name('impersonate.start');
    Route::post('impersonate/stop',        [ManualAuthController::class, 'impersonateStop'])->name('impersonate.stop');

    // ── Exercise 50.4: User Role Management ─────────────────────────────────
    Route::get('users',                         [UserRoleController::class, 'index'])->name('users.index');
    Route::get('users/{user}/roles',            [UserRoleController::class, 'edit'])->name('users.edit-roles');
    Route::patch('users/{user}/roles',          [UserRoleController::class, 'update'])->name('users.update-roles');
    Route::post('users/{user}/roles/assign',    [UserRoleController::class, 'assignRole'])->name('users.assign-role');
    Route::delete('users/{user}/roles/revoke',  [UserRoleController::class, 'revokeRole'])->name('users.revoke-role');

});
