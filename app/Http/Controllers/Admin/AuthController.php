<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Gate};
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLogin(): View
    {
        return view('admin.login');
    }

    /**
     * Show the admin dashboard.
     */
    public function dashboard(\App\Services\CacheMonitorService $monitor): View
    {
        Gate::authorize('view-admin-dashboard');
        $stats = $monitor->stats();
        return view('admin.dashboard', compact('stats'));
    }

    /**
     * Attempt to authenticate an admin and start their session.
     *
     * We explicitly target the `admin` guard so that the customer (`web`)
     * session is never touched during this flow.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email'    => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('admin')->attempt($credentials, $request->boolean('remember'))) {
            // Regenerate the session ID to prevent session-fixation attacks.
            // This does NOT destroy session data — it only rotates the ID.
            $request->session()->regenerate();

            // redirect()->intended() honours the URL the admin was trying to
            // reach before being bounced to the login page.
            return redirect()->intended(route('admin.dashboard'));
        }

        // Use the localization key so the message can be translated without
        // touching PHP source code.
        return back()
            ->withInput($request->only('email', 'remember'))
            ->withErrors(['email' => trans('auth.failed')]);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}