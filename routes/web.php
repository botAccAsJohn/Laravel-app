<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Product2Controller;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Route::controller(Product2Controller::class)->group(function () {
//     Route::get('/products',          'index')->name('products.index');
//     Route::get('/products/create',   'create')->name('products.create');
//     Route::get('/products/{id}',     'show')->name('products.show');
//     Route::get('/products/{id}/edit', 'edit')->name('products.edit');
//     Route::post('/products',         'store')->name('products.store');
//     Route::put('/products/{id}',     'update')->name('products.update');
//     Route::delete('/products/{id}',  'destroy')->name('products.destroy');
// });


Route::get('/generate-link/{id}', function ($id) {
    return URL::temporarySignedRoute(
        'unsubscribe',              // route name
        now()->addMinutes(30),      // expiry time
        ['user' => $id]             // parameter
    );
});

Route::get('/unsubscribe/{user}', function (Request $request, $user) {
    if (!$request->hasValidSignature()) {
        abort(403, 'Invalid or expired link');
    }
    return "User {$user} unsubscribed successfully";
})->name('unsubscribe');


Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('products', Product2Controller::class)->except(['index', 'show']);
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

    // Recently Viewed Routes
    Route::get('/recently-viewed', [\App\Http\Controllers\RecentlyViewController::class, 'index'])->name('recently.index');
    Route::post('/recently-viewed/clear', [\App\Http\Controllers\RecentlyViewController::class, 'clear'])->name('recently.clear');

    Route::resource('orders', OrderController::class);

    // Logs Route
    Route::get('/logs', [Product2Controller::class, 'logs'])->name('logs.index');
});


Route::get('/export-products', [Product2Controller::class, 'exportProducts'])->name('products.export');



require __DIR__ . '/auth.php';
