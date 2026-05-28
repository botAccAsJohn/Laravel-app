<?php
// app/Http/Controllers/DeviceController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DeviceController extends Controller
{
    /**
     * Show the device management page: list all personal access tokens
     * for the currently authenticated web user.
     */
    public function index(Request $request)
    {
        $tokens = $request->user()
            ->tokens()
            ->select(['id', 'name', 'last_used_at', 'created_at', 'expires_at'])
            ->latest()
            ->get();

        return view('profile.devices', compact('tokens'));
    }

    /**
     * Revoke a specific token that belongs to the authenticated user.
     */
    public function revoke(Request $request, int $id)
    {
        $request->user()->tokens()->findOrFail($id)->delete();

        return back()->with('success', 'Token revoked successfully.');
    }

    /**
     * Revoke ALL tokens for the authenticated user (sign out all devices).
     */
    public function revokeAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return back()->with('success', 'All tokens have been revoked.');
    }
}
