<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Exercise 52.1 — Password Reset Link Controller with Rate Limiter.
 *
 * The 'password-reset' rate limiter (3 per hour by email) is applied
 * via the route middleware in web.php rather than here, but the
 * middleware is defined in AppServiceProvider::boot().
 *
 * Why rate-limit by EMAIL, not IP?
 * ─────────────────────────────────
 * See AppServiceProvider::boot() — the key insight is that the attack
 * is targeted at a specific email inbox, so the limiter key must also
 * be the email address. IP-based limits are trivially bypassed with
 * rotating proxies or botnets.
 */
class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to show to the user. Finally, we'll send out a proper response.
        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
