<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Product2Controller;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

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
    if (! $request->hasValidSignature()) {
        abort(403, 'Invalid or expired link');
    }
    return "User {$user} unsubscribed successfully";
})->name('unsubscribe');


Route::middleware(['auth'])->group(function () {
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('products', Product2Controller::class)->except(['index', 'show']);
    });
    Route::resource('products', Product2Controller::class)->only(['index', 'show']);
});
require __DIR__ . '/auth.php';
