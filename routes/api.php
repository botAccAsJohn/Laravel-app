<?php

use App\Models\User;
use App\Models\OrderAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Route, DB, Log, File, Http, URL, Mail};
use App\Http\Controllers\Api\{AuthController,TokenController};      

Route::middleware(['throttle:api', 'api.rate.headers'])->group(function () {

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

    Route::get('/downloadInvoice', function (\App\Services\OrderService $orderService) {
        $order = \App\Models\Order::firstOrFail();
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
    // ── Slack Interactions (Exercise 39.4) ──────────────────────────────
    Route::post('/slack/interactions', [\App\Http\Controllers\Api\SlackInteractionController::class, 'handle'])
        ->middleware(\App\Http\Middleware\VerifySlackSignature::class)
        ->name('slack.interactions');


    Route::get('/order-analytics', function () { //{"main_db_users":2,"analytics_model_count":0,"analytics_raw_query":[{"total":0}]}

        // MAIN DATABASE QUERY
        $users = User::count();

        // ANALYTICS DATABASE USING MODEL
        $analyticsCount = OrderAnalytics::count();

        // RAW QUERY ON ANALYTICS DB
        $rawAnalytics = DB::connection('analytics')
            ->select('SELECT COUNT(*) as total FROM order_analytics');

        return response()->json([
            'main_db_users' => $users,
            'analytics_model_count' => $analyticsCount,
            'analytics_raw_query' => $rawAnalytics,
        ]);
    });

    Route::get('/search', function () {
        $q = request()->query('q');
        // $cat = request()->query('category_id');
        // $tags = request()->query('tags'); // expects comma-separated string or array
        // $inStock = request()->boolean('in_stock_only', false);
        // $sort = request()->query('sort', 'relevance'); // 'relevance' or 'price'

        $products = \App\Models\Product::search($q)->get();

        // if ($cat) {
        //     $products = $products->where('category_id', $cat);
        // }

        // if ($tags) {
        //     $tagsArr = is_array($tags) ? $tags : explode(',', $tags);
        //     $products = $products->whereIn('tags', $tagsArr);
        // }

        // if ($inStock) {
        //     $products = $products->where('quantity', '>', 0);
        // }

        // if ($sort === 'price') {
        //     $products = $products->orderBy('price', 'asc');
        // }
        // else, default is relevance (scout/laravel default)

        return $products;
    })->middleware('throttle:search');


    

// Public
Route::post('/login',    [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',  [AuthController::class, 'logout']);
    Route::get('/user',     fn(Request $r) => $r->user());

    // Device management
    Route::get('/tokens',            [TokenController::class, 'index']);
    Route::delete('/tokens/{id}',    [TokenController::class, 'revoke']);
    Route::delete('/tokens',         [TokenController::class, 'revokeAll']);
});

});
