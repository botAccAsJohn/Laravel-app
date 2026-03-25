<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Product2Controller;
use Illuminate\Support\Facades\Route;

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


Route::middleware(['auth'])->group(function () {
    Route::resource('products', Product2Controller::class)->only(['index', 'show']);
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('products', Product2Controller::class)->except(['index', 'show']);
    });
});
require __DIR__ . '/auth.php';
