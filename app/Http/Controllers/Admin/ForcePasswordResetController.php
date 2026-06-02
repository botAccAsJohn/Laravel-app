<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Mail, Password};

/**
 * Exercise 52.3 — Admin: Force Password Reset
 *
 * Allows an administrator to flag any user account for a mandatory password
 * reset. The user will be redirected away from every page (by the
 * RequirePasswordReset middleware) until they complete the reset flow.
 *
 * Flow
 * ────
 * 1. Admin POSTs to admin/users/{user}/force-reset with an optional reason.
 * 2. We set force_password_reset = true on the user.
 * 3. We invalidate ALL existing sessions for that user via
 *    Auth::logoutOtherDevices() — this is critical; see below.
 * 4. We generate a password reset token via the Password broker.
 * 5. We send a branded alert email with the reset link and the reason.
 * 6. We log the action to the security channel (audit trail).
 *
 * Why must forced reset invalidate all existing sessions?
 * ────────────────────────────────────────────────────────
 * The entire purpose of a forced reset is to kick a potentially compromised
 * actor out of the account. If we only set the flag but don't kill sessions,
 * an attacker who already has an active session can continue using the app
 * indefinitely — the middleware only blocks NEW requests that hit the flag,
 * but an existing authenticated session bypasses that check for its lifetime.
 *
 * Deleting all sessions (except the current admin's) via the sessions table
 * guarantees that any active attacker session is terminated immediately.
 */
class ForcePasswordResetController extends Controller
{
    /**
     * Flag the user for a mandatory password reset.
     *
     * POST admin/users/{user}/force-reset
     */
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $request->input('reason', 'Administrative security policy requires a password change.');
        $admin  = Auth::guard('admin')->user();

        // ── Step 1: Flag the user ────────────────────────────────────────────
        $user->forceFill(['force_password_reset' => true])->save();

        // ── Step 2: Invalidate all sessions for this user ───────────────────
        // Delete every row in `sessions` where user_id = $user->id.
        // We cannot use Auth::logoutOtherDevices() here because we are logged
        // in as an ADMIN, not as the target user. We directly delete from the
        // sessions table instead.
        \Illuminate\Support\Facades\DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // ── Step 3: Generate a password reset token ─────────────────────────
        $token = Password::broker('users')->createToken($user);

        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        // ── Step 4: Send the alert email ─────────────────────────────────────
        Mail::html(
            view('email.forced-password-reset', [
                'name'     => $user->name,
                'reason'   => $reason,
                'resetUrl' => $resetUrl,
            ])->render(),
            function ($message) use ($user) {
                $message
                    ->to($user->email, $user->name)
                    ->subject('⚠️ Action Required: Reset Your Password');
            }
        );

        // ── Step 5: Audit log ─────────────────────────────────────────────────
        Log::channel('security')->warning('[ForcedReset] Admin flagged user for mandatory reset', [
            'admin_email'  => $admin?->email ?? 'unknown',
            'admin_id'     => $admin?->id,
            'user_email'   => $user->email,
            'user_id'      => $user->id,
            'reason'       => $reason,
            'sessions_cleared' => true,
        ]);

        return back()->with('success', "User {$user->name} has been flagged for a mandatory password reset. A reset email has been sent.");
    }
}
