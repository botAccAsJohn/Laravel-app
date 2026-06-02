<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Number;

if (! function_exists('format_price')) {
    function format_price(int|float $amount): string
    {
        return Number::currency($amount);
    }
}

if (! function_exists('order_status_badge')) {
    function order_status_badge(string $status): string
    {
        return match ($status) {
            'pending'   => 'badge bg-warning text-dark',
            'paid'      => 'badge bg-info text-dark',
            'shipped'   => 'badge bg-primary',
            'delivered' => 'badge bg-success',
            default     => 'badge bg-secondary',
        };
    }
}

if (! function_exists('human_file_size')) {
    function human_file_size(int $bytes): string
    {
        return match (true) {
            $bytes >= 1_073_741_824 => number_format($bytes / 1_073_741_824, 2) . ' GB',
            $bytes >= 1_048_576     => number_format($bytes / 1_048_576, 2) . ' MB',
            $bytes >= 1_024         => number_format($bytes / 1_024, 2) . ' KB',
            default                 => $bytes . ' B',
        };
    }
}

// ── Impersonation Helpers ──────────────────────────────────────────────────
// These three functions centralise all session reads for the impersonation
// feature so no controller, middleware, or blade repeats raw session() calls.
//
// Session key set by ImpersonationController::impersonate():
//   'impersonator_id'     → Admin's ID (set on START, cleared via pull() on STOP)
//   'impersonation_log_id'→ ImpersonationLog row ID (for the audit trail)

/**
 * Is an admin currently impersonating a customer?
 *
 * Fast boolean — reads one session key, hits no DB.
 * Use this for guards, middleware, and banner visibility checks.
 *
 * Usage (Blade):   @if(is_impersonating())  OR  @if($isImpersonating)
 * Usage (PHP):     if (is_impersonating()) { … }
 */
if (! function_exists('is_impersonating')) {
    function is_impersonating(): bool
    {
        return session()->has('impersonator_id');
    }
}

/**
 * Return the User model currently being impersonated.
 *
 * Returns the authenticated web-guard user when impersonation is active,
 * or null when there is no active impersonation session.
 *
 * The model is resolved from Auth::guard('web') — the same guard that
 * ManualAuthController::impersonateStart() logs into — so no extra DB
 * query is fired (the guard's user is already hydrated).
 *
 * Usage (Blade):   {{ impersonated_user()?->name }}
 * Usage (PHP):     $user = impersonated_user();
 */
if (! function_exists('impersonated_user')) {
    function impersonated_user(): ?User
    {
        if (! is_impersonating()) {
            return null;
        }

        /** @var User|null */
        return auth()->guard('web')->user();
    }
}

/**
 * Return the Admin model who started the impersonation session.
 *
 * Reads the admin ID from the session and fetches the Admin record.
 * Returns null when no impersonation is active or the admin row is missing.
 *
 * Usage (Blade):   {{ impersonating_admin()?->email }}
 * Usage (PHP):     $admin = impersonating_admin();
 */
if (! function_exists('impersonating_admin')) {
    function impersonating_admin(): ?Admin
    {
        $adminId = session('impersonator_id');

        if (! $adminId) {
            return null;
        }

        return Admin::find($adminId);
    }
}

