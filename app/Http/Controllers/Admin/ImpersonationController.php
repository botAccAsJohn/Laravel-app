<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ImpersonationLog, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\View\View;

class ImpersonationController extends Controller
{

    public function index(): View
    {
        abort_unless(Auth::guard('admin')->check(), 403);

        $customers = User::orderBy('name')->get(['id', 'name', 'email', 'created_at']);
        $logs = ImpersonationLog::with('targetUser')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.impersonate', compact('customers', 'logs'));
    }

    public function impersonate(Request $request, User $user): RedirectResponse
    {
        // ── Gates ─────────────────────────────────────────────────────────
        abort_unless(Auth::guard('admin')->check(), 403, 'Admin authentication required.');
        abort_if(is_impersonating(), 400, 'Already impersonating a user. Stop the current session first.');

        /** @var \App\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        // ── Audit log ──────────────────────────────────────────────────────
        // Write before the swap so the admin ID is still reliably in Auth.
        $log = ImpersonationLog::create([
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_user_id' => $user->id,
            'target_email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        session()->put('impersonator_id', $admin->id);
        session()->put('impersonation_log_id', $log->id);

        Log::warning('[Impersonation] Admin started impersonation', [
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
            'target_user_id' => $user->id,
            'target_email' => $user->email,
            'ip' => $request->ip(),
        ]);


        Auth::guard('web')->loginUsingId($user->id);

        // Regenerate session ID to prevent session-fixation attacks.
        $request->session()->regenerate();

        return redirect()->route('products.index')
            ->with('success', "Now viewing as {$user->name} ({$user->email}).");
    }


    public function stopImpersonating(Request $request): RedirectResponse
    {
        // Atomic get + delete — prevents double-restore on double-submit.
        abort_unless(session()->has('impersonator_id'), 403, 'No active impersonation session.');

        // Close the audit record (pull = get + forget in one step).
        $logId = session()->pull('impersonation_log_id');
        if ($logId) {
            ImpersonationLog::find($logId)?->stop();
        }

        // Remove the impersonator marker — this is what all helpers check.
        session()->pull('impersonator_id');

        // Log out the impersonated customer from the web guard.
        Auth::guard('web')->logout();

        // Regenerate session ID after the identity swap.
        $request->session()->regenerate();

        Log::info('[Impersonation] Admin stopped impersonation', [
            'admin_id' => Auth::guard('admin')->id(),
            'ip' => $request->ip(),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Impersonation ended. Returned to admin context.');
    }
}
