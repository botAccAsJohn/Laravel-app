<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

// Cart Routes ───────────────────
// ───────────────────────────────
Route::middleware('guest_or_customer')->prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add/{productId}', [CartController::class, 'add'])->name('add');
    Route::post('/decrement/{productId}', [CartController::class, 'decrement'])->name('decrement');
    Route::post('/remove/{productId}', [CartController::class, 'remove'])->name('remove');
    Route::post('/clear', [CartController::class, 'clear'])->name('clear');
});

// // Coupon routes — auth required (customers only)
// Route::middleware(['auth'])->prefix('cart/coupon')->name('cart.coupon.')->group(function () {
//     Route::post('/apply',  [CartController::class, 'applyCoupon'])->name('apply');
//     Route::delete('/remove', [CartController::class, 'removeCoupon'])->name('remove');
// });