<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Exercise 52.2 — Password Reset with History & Session Invalidation
 */
class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request): View
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    /**
     * Handle an incoming new password request.
     *
     * Flow
     * ────
     * 1. ResetPasswordRequest validates token, email, strong password rules,
     *    and rejects any of the last 5 previous hashes.
     * 2. Password::reset() verifies the token against DB and calls the closure.
     * 3. Inside the closure we:
     *    a. Save the NEW hash to password_histories (prepend before saving to
     *       $user so Hash::check() in the request uses the *old* password).
     *    b. Prune history to keep only the most recent 5 entries.
     *    c. Persist the new hashed password + a fresh remember_token.
     *    d. Fire PasswordReset event (flushes session, revokes tokens if configured).
     *    e. Call Auth::logoutOtherDevices() to invalidate ALL other sessions —
     *       this protects against session hijacking: if an attacker had an
     *       active session, it is destroyed the moment the password changes.
     */
    public function store(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $newHash = Hash::make($request->password);

                // ── Step 1: Record in password_histories BEFORE saving ──────
                // We store the hash of the NEW password so future resets can
                // check against it. Only hashes are stored — never plain-text.
                PasswordHistory::create([
                    'user_id'  => $user->id,
                    'password' => $newHash,
                ]);

                // Prune to keep only the 5 most recent hashes per user.
                // We delete any entries beyond the 5th-oldest.
                $keepIds = PasswordHistory::where('user_id', $user->id)
                    ->latest('created_at')
                    ->limit(5)
                    ->pluck('id');

                PasswordHistory::where('user_id', $user->id)
                    ->whereNotIn('id', $keepIds)
                    ->delete();

                // ── Step 2: Persist the new password ─────────────────────────────────
                $user->forceFill([
                    'password'             => $newHash,
                    'remember_token'       => Str::random(60),
                    // Exercise 52.3: Clear the forced-reset flag after a successful
                    // reset so the user regains full access to the application.
                    'force_password_reset' => false,
                ])->save();

                // ── Step 3: Fire the PasswordReset event ───────────────────
                event(new PasswordReset($user));

                // ── Step 4: Invalidate all other sessions (Exercise 52.2) ──
                // Auth::logoutOtherDevices() deletes every session row for this
                // user except the current one. This is critical because:
                //   • If an attacker had hijacked a session before the reset,
                //     that session is now destroyed.
                //   • Combines with the remember_token rotation above to also
                //     kick out any "remember me" cookies.
                Auth::logoutOtherDevices($request->password);
            }
        );

        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', __($status))
                    : back()->withInput($request->only('email'))
                        ->withErrors(['email' => __($status)]);
    }
}
