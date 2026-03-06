<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users', CompanyController::class);
Route::get('/discount/{price}', [ProductController::class, 'calculate']);
