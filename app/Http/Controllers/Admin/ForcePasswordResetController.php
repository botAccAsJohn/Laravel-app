<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, DB, Hash, Log, Mail, Password};


class ForcePasswordResetController extends Controller
{
    public function __invoke(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = $request->input('reason', 'Administrative security policy requires a password change.');
        $admin = Auth::guard('admin')->user();

        // ── Step 1: Flag the user ────────────────────────────────────────────
        $user->forceFill(['force_password_reset' => true])->save();


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
                'name' => $user->name,
                'reason' => $reason,
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
            'admin_email' => $admin?->email ?? 'unknown',
            'admin_id' => $admin?->id,
            'user_email' => $user->email,
            'user_id' => $user->id,
            'reason' => $reason,
            'sessions_cleared' => true,
        ]);

        return back()->with('success', "User {$user->name} has been flagged for a mandatory password reset. A reset email has been sent.");
    }
}
