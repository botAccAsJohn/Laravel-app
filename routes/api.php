<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;


Route::middleware('throttle:api')->group(function () {
    Route::get('/user', function (Request $request) {
        return ['status' => 'success', 'message' => 'API is working'];
    });
});

Route::get('/string', function () {
    return "hello";
});
// auto convert to json
Route::get('/json', function () {
    return response()->json();
});
// auto convert to view
Route::get('/view', function () {
    return view('welcome');
});
Route::get('/modifyJson', function () {
    $products = [
        ['id' => 1, 'name' => 'Laptop', 'price' => 999],
        ['id' => 2, 'name' => 'Phone',  'price' => 499],
    ];

    return response()->json([
        'status'  => true,
        'message' => 'Products fetched successfully',
        'data'    => $products,
    ]);
})->name('api.modifyJson');


Route::get('/download', function () {
    // Get all files in the 'products' directory inside 'public'
    $files = File::files(storage_path('app\public\products'));

    // Check if there are any files
    if (empty($files)) {
        abort(404, 'No files found in the products folder.');
    }

    // Get the first file from the array
    $firstFile = $files[0]->getPathname();

    // Return the file as a download response
    return response()->download($firstFile, 'img.png');
});

Route::get('/redirect', function () {
    return redirect()->route('api.modifyJson')->with('message', 'You are being redirected!!');
});
