<?php

use App\Models\Permission;
use App\Models\User;
use App\Models\OrderAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Route, DB, Log, File, Http, URL, Mail};
use App\Http\Controllers\Api\{AuthController, TokenController, ApiKeyController};

Route::middleware(['auth:sanctum', 'throttle:api-tiered'])->group(function () {
    Route::get('/download', function () {
        $files = File::files(storage_path('app\public\products'));
        if (empty($files)) {
            abort(404, 'No files found in the products folder.');
        }
        $firstFile = $files[0]->getPathname();
        return response()->download($firstFile, 'img.png');
    });

    Route::get('/downloadInvoice', function (\App\Services\OrderService $orderService) {
        $order = \App\Models\Order::firstOrFail();
        return $orderService->downloadInvoice($order);
    });

    // Routes for External API Service
    Route::get('/external-users', [\App\Http\Controllers\UserController::class, 'index']);
    Route::get('/external-users/{id}', [\App\Http\Controllers\UserController::class, 'show']);
    Route::post('/external-users', [\App\Http\Controllers\UserController::class, 'store']);

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', fn(Request $r) => $r->user());

    // Device management (Sanctum tokens)
    Route::get('/tokens', [TokenController::class, 'index']);
    Route::delete('/tokens/{id}', [TokenController::class, 'revoke']);
    Route::delete('/tokens', [TokenController::class, 'revokeAll']);

    // Exercise 53.3: API Key Management
    Route::get('/api-keys', [ApiKeyController::class, 'index']);
    Route::post('/api-keys', [ApiKeyController::class, 'store']);
    Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy']);
});

// Public and standard API routes (uses basic throttle:api)
Route::middleware(['throttle:api', 'api.rate.headers'])->group(function () {

    Route::middleware(['auth:sanctum'])->group(function () {
        // Note: Authenticated routes are now handled by the tier-based limiter above
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

    // generate the signed url
    Route::get('/generate-link/{id}', function ($id) {
        return URL::temporarySignedRoute(
            'unsubscribe',              // route name
            now()->addMinutes(30),      // expiry time
            ['user' => $id]             // parameter
        );
    });

    // validate the above signed urls
    Route::get('/unsubscribe/{user}', function (Request $request, $user) {
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }
        return "User {$user} unsubscribed successfully";
    })->name('unsubscribe');



    //need to chake this one 

    // ── Exercise 54.3: Encrypted Payloads (alternative to Signed URLs) ─────
    Route::get('/generate-encrypted-link/{id}', function ($id) {
        // We use JSON encoding + Crypt::encryptString() to avoid PHP serialization/object-injection risks.
        $payload = Crypt::encryptString(json_encode([
            'user' => $id,
            'expires_at' => now()->addHour()->timestamp
        ]));

        return response()->json([
            'url' => url('/api/unsubscribe-encrypted/' . urlencode($payload))
        ]);
    });

    Route::get('/unsubscribe-encrypted/{payload}', function ($payload) {
        try {
            // Decrypt the raw string payload safely (no unserialize)
            $decryptedJson = Crypt::decryptString(urldecode($payload));
            $data = json_decode($decryptedJson, true);

            if (!$data || !isset($data['user']) || !isset($data['expires_at'])) {
                abort(400, 'Malformed payload.');
            }

            // Validate expiry
            if (time() > $data['expires_at']) {
                abort(403, 'This link has expired.');
            }

            return response()->json([
                'status' => 'success',
                'message' => "User {$data['user']} unsubscribed successfully via encrypted payload.",
            ]);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            abort(403, 'Invalid or tampered encrypted payload.');
        }
    });


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

    // Exercise 44.2: Cursor pagination for efficient scrolling of large datasets
    Route::get('/orders', function (Request $request) {
        $user = $request->user();

        $orders = \App\Models\Order::query()
            ->where('user_id', $user->id)
            ->latest('placed_at')
            ->with(['items.product'])
            ->cursorPaginate(20);

        return response()->json([
            'data' => $orders->items(),
            'pagination' => [
                'next_cursor' => $orders->nextCursor()?->encode(),
                'prev_cursor' => $orders->previousCursor()?->encode(),
                'per_page' => 20,
            ]
        ]);
    })->middleware(['auth:sanctum', 'throttle:api-tiered']);



    // Public
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);

    // Protected routes now handled above with tier-based rate limiting

    // ── Debug: test user permissions ─────────────────────────────────────────
    Route::get('/testing-permission', function () {
        $user = request()->user();
        dd($user);
        // $user = request()->user();
        if (! $user) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }
        return response()->json([
            'user'        => $user->email,
            'roles'       => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
    })->middleware('web');
    
});

