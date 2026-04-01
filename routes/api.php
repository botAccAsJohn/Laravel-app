<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;
use App\Exceptions\ProductOutOfStockException;


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

// Route::get('/redis', function () {
//     // Set a test key OUTSIDE the loop so we have something to see
//     Redis::set('debug_active_at', now()->toDateTimeString());
//     Redis::set('name', 'Kesha');

//     $keys = Redis::keys('*');
//     $data = [];

//     foreach ($keys as $key) {
//         // Strip out the prefix if it exists to make it readable
//         $prefix = config('database.redis.options.prefix');
//         $cleanKey = $key;
//         if (str_starts_with($key, $prefix)) {
//             $cleanKey = substr($key, strlen($prefix));
//         }

//         $type = Redis::type($key);

//         switch ($type) {
//             case 'string':
//                 $value = Redis::get($key);
//                 $decoded = json_decode($value, true);
//                 $data[$cleanKey] = $decoded ?? $value;
//                 break;
//             case 'hash':
//                 $data[$cleanKey] = Redis::hgetall($key);
//                 break;
//             case 'list':
//                 $data[$cleanKey] = Redis::lrange($key, 0, -1);
//                 break;
//             default:
//                 $data[$cleanKey] = 'Type: ' . $type;
//         }
//     }
//     return $data;
// });



Route::get('/redis', function () {
    try {
        // Test basic connection
        Redis::ping();

        // Test write operation
        Redis::set('test_key', 'Hello from Laravel!');

        // Test read operation
        $value = Redis::get('test_key');

        // Get Redis info
        $info = Redis::connection()->client()->info();

        return response()->json([
            'status' => 'success',
            'message' => 'Redis is connected!',
            'test_value' => $value,
            'redis_version' => $info['redis_version'] ?? 'unknown',
            'connected_clients' => $info['connected_clients'] ?? 'unknown',
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Redis connection failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});
// routes/web.php

Route::get('/test-session', function () {
    try {
        // Store data in session
        session(['test_user' => 'John Doe']);
        session(['test_time' => now()->toDateTimeString()]);

        // Retrieve data
        $user = session('test_user');
        $time = session('test_time');

        // Get session ID
        $sessionId = session()->getId();

        return response()->json([
            'status' => 'success',
            'message' => 'Session is working with Redis!',
            'session_id' => $sessionId,
            'session_driver' => config('session.driver'),
            'data' => [
                'user' => $user,
                'time' => $time,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Session test failed',
            'error' => $e->getMessage(),
        ], 500);
    }
});

// routes/web.php
Route::get('/redis-keys', function () {
    try {
        // Get all keys matching session pattern
        $prefix = config('database.redis.options.prefix');
        $sessionKeys = Redis::keys($prefix . 'session:*');

        // Get all keys (be careful on production with large datasets)
        $allKeys = Redis::keys('*');

        $sessionData = [];
        foreach ($sessionKeys as $key) {
            // Remove prefix to get clean key
            $cleanKey = str_replace($prefix, '', $key);
            $sessionData[$cleanKey] = Redis::get($key);
        }

        return response()->json([
            'status' => 'success',
            'prefix' => $prefix,
            'total_keys' => count($allKeys),
            'session_keys_count' => count($sessionKeys),
            'session_keys' => $sessionKeys,
            'session_data' => $sessionData,
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'error' => $e->getMessage(),
        ], 500);
    }
});


Route::get('/exception', function () {
    // throw new ProductOutOfStockException(
    //     productName: 'Laptop',
    //     productId: 1,
    //     requestedQty: 1,
    //     availableQty: 0,
    // );
    Log::channel('orders')->info('This is an emergency message');
});
