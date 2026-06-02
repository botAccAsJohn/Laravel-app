<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{ImpersonationLog, User};
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, Log};
use Illuminate\View\View;

/**
 * Handles admin impersonation of customer accounts.
 *
 * Architecture note — why we keep the dual-guard approach
 * ────────────────────────────────────────────────────────
 * This app has two separate authentication tables (users / admins) and two
 * guards (web / admin). The community pattern of Auth::loginUsingId($adminId)
 * on stop would look in the USERS table — not the admins table — so it would
 * fail or log in the wrong account.
 *
 * Instead, we follow the session-swap strategy but adapt it for multi-guard:
 *
 *   START  →  store admin ID in session  →  Auth::guard('web')->loginUsingId($userId)
 *   STOP   →  session()->pull('impersonator_id')  →  Auth::guard('web')->logout()
 *             (the admin guard is still alive — the admin was never logged out)
 *
 * All views and helpers check session()->has('impersonator_id') — the same key
 * the community pattern uses — so Blade, controllers, and middleware all stay
 * consistent with no extra coupling.
 */
class ImpersonationController extends Controller
{
    /**
     * Show the impersonation management page.
     *
     * Lists all customers + the last 50 impersonation log entries.
     */
    public function index(): View
    {
        abort_unless(Auth::guard('admin')->check(), 403);

        $customers = User::orderBy('name')->get(['id', 'name', 'email', 'created_at']);
        $logs      = ImpersonationLog::with('targetUser')
            ->latest()
            ->take(50)
            ->get();

        return view('admin.impersonate', compact('customers', 'logs'));
    }

    /**
     * Start impersonating a customer account.
     *
     * Gates:
     *   • Only authenticated admins can impersonate.
     *   • An admin cannot impersonate themselves (guard mismatch makes this
     *     impossible in practice, but the check is explicit for clarity).
     *
     * Flow:
     *   1. Store admin ID in session under 'impersonator_id'.
     *   2. Write an ImpersonationLog row for the audit trail.
     *   3. Switch the web guard to the target customer via loginUsingId().
     *   4. Regenerate the session to prevent session-fixation attacks.
     */
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
            'admin_id'       => $admin->id,
            'admin_email'    => $admin->email,
            'target_user_id' => $user->id,
            'target_email'   => $user->email,
            'ip_address'     => $request->ip(),
            'user_agent'     => $request->userAgent(),
        ]);

        // ── Session swap ───────────────────────────────────────────────────
        // Store both the admin ID (to display in the banner) and the log ID
        // (to close the audit record on stop). Using two small keys is cleaner
        // than serialising the entire Admin model into the session.
        session()->put('impersonator_id',     $admin->id);
        session()->put('impersonation_log_id', $log->id);

        Log::warning('[Impersonation] Admin started impersonation', [
            'admin_id'       => $admin->id,
            'admin_email'    => $admin->email,
            'target_user_id' => $user->id,
            'target_email'   => $user->email,
            'ip'             => $request->ip(),
        ]);

        // Switch the web guard to the target customer.
        // loginUsingId() persists across the entire session (unlike onceUsingId).
        Auth::guard('web')->loginUsingId($user->id);

        // Regenerate session ID to prevent session-fixation attacks.
        $request->session()->regenerate();

        return redirect()->route('products.index')
            ->with('success', "Now viewing as {$user->name} ({$user->email}).");
    }

    /**
     * Stop impersonating and return to the admin context.
     *
     * session()->pull() is atomic: it reads AND removes the key in one call —
     * no risk of a double-restore if the user hits the button twice.
     *
     * The admin guard is still live (the admin was never logged out), so there
     * is nothing to re-authenticate — just clear the web guard and redirect.
     */
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
            'ip'       => $request->ip(),
        ]);

        return redirect()->route('admin.dashboard')
            ->with('success', 'Impersonation ended. Returned to admin context.');
    }
}
