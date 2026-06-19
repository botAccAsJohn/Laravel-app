<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PasswordHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * Exercise 52.2 — Profile password change with history enforcement.
 */
class PasswordController extends Controller
{
    /**
     * Update the user's password (from the profile page).
     *
     * Enforces:
     *  • Same strong-password rules as registration and reset.
     *  • Rejects any of the last 5 previously used passwords.
     *  • Records the new hash to password_histories.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            // Exercise 52.2: Strong password rules on profile change too.
            'password' => [
                'required',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(),
                'confirmed',
            ],
        ]);

        $user        = $request->user();
        $newPassword = $validated['password'];

        // Block reuse of the last 5 passwords.
        $reused = PasswordHistory::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(5)
            ->get()
            ->first(fn ($history) => Hash::check($newPassword, $history->password));

        if ($reused) {
            return back()->withErrors([
                'password' => 'This password was used recently. Please choose a different password.',
            ], 'updatePassword');
        }

        $newHash = Hash::make($newPassword);

        // Record new hash in history, prune to 5.
        PasswordHistory::create([
            'user_id'  => $user->id,
            'password' => $newHash,
        ]);

        $keepIds = PasswordHistory::where('user_id', $user->id)
            ->latest('created_at')
            ->limit(5)
            ->pluck('id');

        PasswordHistory::where('user_id', $user->id)
            ->whereNotIn('id', $keepIds)
            ->delete();

        $user->update(['password' => $newHash]);

        return back()->with('status', 'password-updated');
    }
}
