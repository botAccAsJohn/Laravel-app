<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{App, Auth, Session};
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Priority chain for locale resolution:
     * 1. Authenticated user's database preference (preferred_locale)
     * 2. Session value ('locale')
     * 3. Application configuration default (config('app.locale'))
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Runtime switcher via query string (persists to session and DB)
        if ($request->has('lang')) {
            $lang = $request->query('lang');
            $supported = ['en', 'hi', 'ar'];

            if (in_array($lang, $supported)) {
                Session::put('locale', $lang);
                if ($user = $request->user()) {
                    $user->preferred_locale = $lang;
                    $user->save();
                }
            }
        }

        // 2. Resolve active locale based on priority chain
        $user = $request->user();

        if (Session::has('locale')) {
            $locale = Session::get('locale');
        } elseif ($user && $user->preferred_locale) {
            $locale = $user->preferred_locale;
        } else {
            $locale = config('app.locale', 'en');
        }

        // 3. Set the application locale
        App::setLocale($locale);

        // 4. Configure Carbon and Number helper for the active locale
        \Carbon\Carbon::setLocale($locale);
        \Illuminate\Support\Number::useLocale($locale);
        // \Illuminate\Support\Number::useCurrency($locale);

        //write the arr of currcy code that make the changes in the useCurrency
        $currency = ['en' => 'USD', 'hi' => 'INR', 'ar' => 'AED'];
        \Illuminate\Support\Number::useCurrency($currency[$locale]);

        return $next($request);
    }
}
