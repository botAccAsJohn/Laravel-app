<?php

namespace App\Providers;

use App\Services\{OrderService, ExternalApiService, CartService, AIService, LoginThrottleService};
use App\Listeners\HandleFailedJob;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\{Event, Gate, URL, Log, RateLimiter, Blade, View, Auth, Response, DB, Http, Mail, Schema};
use App\Auth\RedisUserProvider;
use App\Models\{Admin, Permission, Product, Review, User};
use Illuminate\Pagination\Paginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Notifications\{VerifyEmail, ResetPassword};
use Carbon\Carbon;
use Illuminate\Contracts\Support\DeferrableProvider;

class AppServiceProvider extends ServiceProvider //implements DeferrableProvider
{
    public function register(): void
    {

        $this->app->singleton(ExternalApiService::class);

        $this->app->singleton(LoginThrottleService::class);
    }

    public function boot(): void
    {
        VerifyEmail::createUrlUsing(function (object $notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            $locale = $notifiable->preferred_locale ?? app()->getLocale();
            return (new \Illuminate\Notifications\Messages\MailMessage)
                ->subject(__('mail.verify_subject', [], $locale))
                ->line(__('mail.verify_intro', [], $locale))
                ->action(__('mail.verify_action', [], $locale), $url)
                ->line(__('mail.verify_footer', [], $locale));
        });

        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token) {
            $locale = $notifiable->preferred_locale ?? app()->getLocale();
            $name = $notifiable->name ?? $notifiable->email;

            $url = url(route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new \Illuminate\Mail\Mailable)
                ->subject(__('reset_subject', [], $locale))
                ->html(
                    view('email.password-reset', [
                        'resetUrl' => $url,
                        'name' => $name,
                        'locale' => $locale,
                    ])->render()
                );
        });

        View::composer(['layouts.*', 'admin.*', 'dashboard'], function ($view) {
            $user = (Auth::guard('admin')->check() && !is_impersonating()) ? Auth::guard('admin')->user() : Auth::user();
            $view->with('user', $user);
        });
        View::composer('layouts.navigation', \App\View\Composers\NavigationComposer::class);
        Blade::directive('currency', function ($amount) {
            return "<?php echo format_price($amount ?? 0); ?>";
        });

        Paginator::useTailwind();// Tailwind CSS

        Response::macro('success', function ($data) {
            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        });

        Http::macro('jsonApi', function () {
            return Http::acceptJson()
                ->baseUrl('https://fakestoreapi.com')
                ->withHeaders(['X-API-KEY' => config('services.fake-api.key')]);
        });

        if ($this->app->environment('local')) {
            Mail::alwaysTo(config('mail.admin_email'));
            Model::preventLazyLoading();
            DB::listen(function ($query) {
                if ($query->time >= 100) {
                    Log::channel('SlowQueries')->info('slow query', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms',
                    ]);
                }
                Log::channel('DBInteraction')->info('SQL Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                ]);
            });
        }

        if (config('services.slack.notifications.bot_user_oauth_token')) {
            \Illuminate\Support\Facades\Notification::route('slack', config('services.slack.notifications.bot_user_oauth_token'));
        }

        Event::listen(JobFailed::class, HandleFailedJob::class);

        \Illuminate\Support\Collection::macro('paginate', function ($perPage, $total = null, $page = null, $pageName = 'page') {
            $page = $page ?: \Illuminate\Pagination\Paginator::resolveCurrentPage($pageName);
            return new \Illuminate\Pagination\LengthAwarePaginator(
                $this->forPage($page, $perPage),
                $total ?: $this->count(),
                $perPage,
                $page,
                [
                    'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                    'pageName' => $pageName,
                ]
            );
        });
        Auth::provider('redis-eloquent', function ($app, array $config) {
            return new RedisUserProvider(
                $app['hash'],
                $config['model']
            );
        });

        // Switch the default guard to whichever guard is active on this request.
        // Must run AFTER the redis-eloquent provider is registered above.
        // Wrapped in rescue() so CLI/artisan commands (which have no session) are safe.
        rescue(function () {
            if ($activeGuard = current_guard()) {
                Auth::shouldUse($activeGuard);
            }
        });

        \Illuminate\Notifications\DatabaseNotification::saved(function ($notification) {
            \Illuminate\Support\Facades\Cache::forget("user:{$notification->notifiable_id}:notifications:latest");
            \Illuminate\Support\Facades\Cache::forget('unread_count_' . $notification->notifiable_id);
        });

        \Illuminate\Notifications\DatabaseNotification::deleted(function ($notification) {
            \Illuminate\Support\Facades\Cache::forget("user:{$notification->notifiable_id}:notifications:latest");
            \Illuminate\Support\Facades\Cache::forget('unread_count_' . $notification->notifiable_id);
        });
    }
}
