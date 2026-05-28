<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\LoginThrottleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * Passes $requiresCaptcha so the view can conditionally show the hCaptcha
     * widget when the account has ≥10 failed attempts.
     */
    public function create(Request $request): View
    {
        $email           = $request->old('email', '');
        $requiresCaptcha = false;

        if ($email) {
            $requiresCaptcha = app(LoginThrottleService::class)->requiresCaptcha($email);
        }

        return view('auth.login', compact('requiresCaptcha'));
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('products.index', absolute: false));
    }

    /**
     * Destroy an authenticated session (customer web guard only).
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
