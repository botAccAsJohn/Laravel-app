<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\LifecycleController;
use App\Http\Controllers\PaymentController;

Route::get('/lifecycle', [LifecycleController::class, 'index'])
    ->middleware('web');

Route::get('/pay', [PaymentController::class, 'pay']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users', CompanyController::class);
Route::get('/discount/{price}', [ProductController::class, 'calculate']);
