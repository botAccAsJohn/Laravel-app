<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Log;
use App\Services\PaymentService;
use App\Services\LoggerService;
use App\Services\MathService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Blade;
use App\Services\AIService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Log::info('[STEP 2b] AppServiceProvider register() called');
        $this->app->bind(PaymentService::class, function ($app) {
            return new PaymentService();
        });
        $this->app->bind('logger.bind', function () {
            return new LoggerService();
        });
        $this->app->singleton('logger.singleton', function () {
            return new LoggerService();
        });
        $this->app->singleton('math.service', function () {
            return new MathService();
        });
        $this->app->singleton(AIService::class, function () {
            return new AIService();
        });
    }

    public function boot(): void
    {
        // [STEP 5] Runs after all providers are registered
        Log::info('[STEP 5] AppServiceProvider boot() called all services ready');

        \Illuminate\Support\Facades\RateLimiter::for('api', function (\Illuminate\Http\Request $request) {
            return \Illuminate\Cache\RateLimiting\Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
        View::composer('*', function ($view) {
            $view->with('user', Auth::user());
        });
        Blade::directive('currency', function ($amount) {
            return "<?php echo '₹' . number_format($amount, 2); ?>";
        });
        Response::macro('success', function ($data) {
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        });
        DB::listen(function ($query) {
            Log::channel('DBInteraction')->info('SQL Query', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time . 'ms',
            ]);
        });
    }
}
