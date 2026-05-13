<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Route, Log, File, Http, URL, Mail};

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
        ['id' => 2, 'name' => 'Phone', 'price' => 499],
    ];

    return response()->json([
        'status' => true,
        'message' => 'Products fetched successfully',
        'data' => $products,
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

Route::get('/downloadInvoice', function (\App\Services\OrderService $orderService) {
    $order = \App\Models\Order::find(1);
    return $orderService->downloadInvoice($order);
});

Route::get('/calling/getProducts', [\App\Http\Controllers\FakeStoreController::class, 'index']);
Route::get('/calling/post', function () {
    $response = Http::post('https://fakestoreapi.com/products', [
        "title" => "string",
        "price" => 100,
        "description" => "string",
        "category" => "string",
        "image" => "http://example.com"
    ]);
    if ($response->successful()) {
        Log::info('POST Success', [
            'status' => $response->status(),
            'body' => $response->json()
        ]);
    } else {
        Log::error('POST Failed', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);
    }
    return response()->json($response->json());
});

// Routes for External API Service
Route::get('/external-users', [\App\Http\Controllers\UserController::class, 'index']);
Route::get('/external-users/{id}', [\App\Http\Controllers\UserController::class, 'show']);
Route::post('/external-users', [\App\Http\Controllers\UserController::class, 'store']);
Route::get('/pool', function () {
    $responses = Http::pool(function ($http) {
        return [
            $http->get('https://fakestoreapi.com/products'),
            $http->get('https://fakestoreapi.com/users'),
        ];
    });

    $products = $responses[0]->json();
    $users = $responses[1]->json();

    return response()->json([
        'products' => $products,
        'users' => $users,
    ]);
});

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

// ── Slack Interactions (Exercise 39.4) ──────────────────────────────
Route::post('/slack/interactions', [\App\Http\Controllers\Api\SlackInteractionController::class, 'handle'])
    ->middleware(\App\Http\Middleware\VerifySlackSignature::class)
    ->name('slack.interactions');
