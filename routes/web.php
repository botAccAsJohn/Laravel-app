<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\Product2Controller;
use App\Http\Controllers\LifecycleController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\GreetingService;
use App\Facades\Math;
use App\Models\Product;


Route::get('/lifecycle', [LifecycleController::class, 'index'])
    ->middleware('web');

Route::get('/test-bind', function () {
    // Ask the container for logger.bind TWICE
    $logger1 = app('logger.bind'); // app() resolves from the container
    $logger2 = app('logger.bind');

    // Log the IDs — are they the same or different?
    Log::info("bind() — logger1 ID: {$logger1->id}");
    Log::info("bind() — logger2 ID: {$logger2->id}");
    Log::info("bind() — Same instance? " . ($logger1 === $logger2 ? 'YES' : 'NO'));

    $logger1->log("Hello from logger1");
    $logger2->log("Hello from logger2");

    return "Check your logs!";
});

Route::get('/test-singleton', function () {
    // Ask the container for logger.singleton TWICE
    $logger1 = app('logger.singleton');
    $logger2 = app('logger.singleton');

    Log::info("singleton() — logger1 ID: {$logger1->id}");
    Log::info("singleton() — logger2 ID: {$logger2->id}");
    Log::info("singleton() — Same instance? " . ($logger1 === $logger2 ? 'YES' : 'NO'));

    $logger1->log("Hello from logger1");
    $logger2->log("Hello from logger2");

    return "Check your logs!";
});

Route::get('/greet/{name}', function (GreetingService $greeting, string $name) {
    return $greeting->greet($name);
});

Route::get('/pay', [PaymentController::class, 'pay']);

Route::get('/', function () {
    return view('welcome');
});

Route::get('/users', CompanyController::class);
Route::get('/discount/{price}', [ProductController::class, 'calculate']);

Route::get('/test-facade', function () {
    Log::info("Process started");
    Cache::put('username_key', 'nothing', now()->addMinutes(1));

    $cachedValue = Cache::get('username_key');

    Log::info("Process ended");
    return $responseData = [
        'name_from_cache' => $cachedValue,
    ];
});

Route::get('/math', function () {

    $add        = Math::add(10, 5);        // 15
    $subtract   = Math::subtract(10, 5);   // 5
    $multiply   = Math::multiply(10, 5);   // 50
    $divide     = Math::divide(10, 5);     // 2
    $percentage = Math::percentage(200, 25); // 50

    Log::info('Math via alias', compact('add', 'subtract', 'multiply', 'divide', 'percentage'));

    return [
        'add'        => $add,
        'subtract'   => $subtract,
        'multiply'   => $multiply,
        'divide'     => $divide,
        'percentage' => $percentage,
    ];
});

Route::get('/get', function () {
    Log::info('GET /get was visited');
    return 'Hello from a GET route!';
});

Route::post('/post', function () {
    return response()->json([
        'message' => 'Received successfully',
    ], 201);
});

Route::get('/getuser/{id?}', function (?string $id = null) {
    if ($id == null) {
        return "no userID is provided";
    }
    return "your userID is : " . $id;
});

// named route
// Route::get('/dashboard', function () {
//     return "this is dashboard";
// })->name('dashbord');
// Route::get('/redirect-to-dashboard', function () {
//     return redirect()->route('dashbord');
// });

Route::redirect('/Home', '/dashboard');

Route::group([], function () {
    Route::get('/profile', function () {
        return "this is profile";
    });
    Route::get('/settings', function () {
        return "this is settings";
    });
});

Route::prefix('admin')->group(function () {
    Route::get('dashboard', function () {
        return "this is admin dashboard";
    });
    Route::get('profile', function () {
        return "this is admin profile";
    });
});

// Route::get('/test-middleware', function () {
//     return "This is a protected page.";
// })->middleware('auth');
// Route::middleware('auth')->group(function () {
//     Route::get('/profile', function () {
//         return "this is profile";
//     });
//     Route::get('/settings', function () {
//         return "this is settings";
//     });
// });

Route::get('/products/{product}', function (Product $product) {
    return $product; // this will search in model and return it if found
});

Route::get('/dashboard', DashboardController::class)->middleware('role:admin');

Route::resource('/products', Product2Controller::class);

Route::fallback(function () {
    return "404 Not Found";
});
