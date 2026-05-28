<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, Redirect, Log};
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    // ── Exercise 49.5: Remember Me & Session Lifetime ────────────────────

    /**
     * Log out all other devices (every session except the current one).
     *
     * Auth::logoutOtherDevices($password) does three things atomically:
     *   1. Re-validates the password (prevents a stolen unlocked machine
     *      from silently kicking the real owner out everywhere).
     *   2. Rotates the `remember_token` column — invalidates ALL remember-me
     *      cookies issued to other devices instantly.
     *   3. Deletes all rows in the `sessions` table except the current
     *      session ID, so other browsers are bounced on their next request.
     */
    public function logoutOtherDevices(Request $request): RedirectResponse
    {
        $request->validateWithBag('logoutOtherDevices', [
            'password' => ['required', 'current_password'],
        ]);

        Auth::logoutOtherDevices($request->password);

        Log::channel('security')->info('[Session] User logged out other devices', [
            'user_id' => Auth::id(),
            'ip'      => $request->ip(),
        ]);

        $request->session()->regenerateToken();

        return Redirect::route('profile.edit')
            ->with('status', 'devices-cleared');
    }
}
