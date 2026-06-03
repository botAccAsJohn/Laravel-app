<?php
// app/Providers/AuthServiceProvider.php
//
// Exercise 50.1 — Application-wide Gates.
//
// DESIGN NOTE: This project uses TWO authentication guards:
//   • web   → App\Models\User  (customers)
//   • admin → App\Models\Admin (administrators)
//
// Laravel's Gate always receives the *currently authenticated user* from the
// **default guard** (web). For gates that govern admin capabilities we therefore
// check Auth::guard('admin') explicitly rather than relying on the injected
// $user argument, which would be null for admin-only requests.
//
// Gate::before()  — runs BEFORE every gate check; returning true short-circuits
//                   all further checks (super-admin bypass).
// Gate::after()   — runs AFTER every gate check; receives the actual result;
//                   used here for the authorization audit log (Module 25).
// Gate::define()  — registers a named capability with its closure.

namespace App\Providers;

use App\Models\{Admin, Product, Review, User};
use Illuminate\Support\Facades\{Auth, Gate, Log};
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerGateBefore();
        $this->registerGates();
        $this->registerGateAfter();
    }

    // ── Gate::before() ── super-admin bypass ─────────────────────────────────
    //
    // Called before *every* gate check. When this closure returns a non-null
    // value that value is used as the gate result without evaluating the gate's
    // own closure. Returning null falls through to the gate definition.
    //
    // We support two super-admin patterns:
    //   1. Admin guard user whose email is in SUPER_ADMIN_EMAILS (.env list)
    //   2. Web guard user whose `role` = 'super-admin' (future extensibility)
    private function registerGateBefore(): void
    {
        Gate::before(function (User|Admin|null $user, string $ability) {
            // If the admin is impersonating, do not allow super-admin bypass to apply.
            // They should experience the exact permissions of the customer they are impersonating.
            if (is_impersonating()) {
                return null;
            }

            // ── Pattern 1: admin-guard super-admin ──────────────────────────
            $adminUser = Auth::guard('admin')->user();
            if ($adminUser instanceof Admin) {
                $superEmails = array_map(
                    'trim',
                    explode(',', config('auth.super_admin_emails', ''))
                );
                if (in_array($adminUser->email, array_filter($superEmails), true)) {
                    Log::channel('security')->info('[Gate::before] Super-admin bypass', [
                        'admin_email' => $adminUser->email,
                        'ability'     => $ability,
                    ]);
                    return true; // grants everything; no further gate check
                }
            }

            // ── Pattern 2: web guard super-admin (future) ───────────────────
            if ($user instanceof User && $user->role === 'super-admin') {
                return true;
            }

            return null; // fall through to the gate definition
        });
    }

    // ── Gate::define() — named capability gates ───────────────────────────────
    //
    // Each closure receives the *web-guard* $user (nullable when unauthenticated).
    // For gates that are admin-only we check Auth::guard('admin')->check()
    // explicitly. This is intentional — admins are a separate Eloquent model.
    private function registerGates(): void
    {
        // ── view-admin-dashboard ─────────────────────────────────────────────
        // Who: any authenticated admin (admin guard).
        Gate::define('view-admin-dashboard', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── manage-products ──────────────────────────────────────────────────
        // Who: authenticated admins. Could be narrowed to specific admin roles.
        Gate::define('manage-products', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── manage-orders ────────────────────────────────────────────────────
        // Who: authenticated admins.
        Gate::define('manage-orders', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── impersonate-users ────────────────────────────────────────────────
        // Who: authenticated admins only. Regular users must never impersonate.
        Gate::define('impersonate-users', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── view-analytics ───────────────────────────────────────────────────
        // Who: authenticated admins.
        Gate::define('view-analytics', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── manage-reports ───────────────────────────────────────────────────
        // Who: authenticated admins.
        Gate::define('manage-reports', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── view-logs ────────────────────────────────────────────────────────
        // Who: authenticated admins.
        Gate::define('view-logs', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── send-admin-alerts ────────────────────────────────────────────────
        // Who: authenticated admins.
        Gate::define('send-admin-alerts', function (User|Admin|null $user) {
            return Auth::guard('admin')->check() && !is_impersonating();
        });

        // ── view-own-orders ──────────────────────────────────────────────────
        // Who: authenticated web (customer) users. Demo of a customer-scoped gate.
        Gate::define('view-own-orders', function (User $user) {
            return true; // any authenticated customer
        });

        // ── Exercise 50.3: Multi-parameter gate ──────────────────────────────
        //
        // Gate::define() accepts additional model arguments beyond $user.
        // Both are resolved from the array passed to Gate::check() / @can().
        //
        // Pattern from the exercise (Comment + Post):
        //   Gate::define('edit-comment', fn (User $u, Comment $c, Post $p)
        //       => $u->id === $c->user_id || $u->id === $p->user_id);
        //
        // Domain equivalent in this project (Review + Product):
        //   A customer may edit a review if they authored it OR if they own
        //   the product being reviewed (seller moderation use-case).
        //
        // Usage in Blade:   @can('edit-review-on-product', [$review, $product])
        // Usage in PHP:     Gate::check('edit-review-on-product', [$review, $product])
        Gate::define('edit-review-on-product', function (User $user, Review $review, Product $product) {
            // Author of the review can always edit it (within 24h — enforced in Policy).
            if ($user->id === $review->user_id) {
                return true;
            }

            // Future: product owner / seller could moderate their own product's reviews.
            // $user->id === $product->seller_id

            return false;
        });
    }

    // ── Gate::after() — authorization audit log ───────────────────────────────
    //
    // Called after every gate check regardless of outcome. $result is the
    // boolean (or null) returned by the gate closure or before hook.
    // Returning a value here overrides the result; returning null preserves it.
    private function registerGateAfter(): void
    {
        Gate::after(function (User|Admin|null $user, string $ability, ?bool $result, mixed $arguments) {
            // Only write to the audit log for non-trivial abilities to avoid
            // flooding the security log with view-* checks during page renders.
            $auditAbilities = [
                'manage-products',
                'manage-orders',
                'impersonate-users',
                'view-analytics',
                'manage-reports',
                'send-admin-alerts',
                'view-logs',
            ];

            if (! in_array($ability, $auditAbilities, true)) {
                return null; // don't override the result
            }

            $adminUser = Auth::guard('admin')->user();
            $actor     = $adminUser
                ? "Admin:{$adminUser->email}"
                : ($user ? "User:{$user->email}" : 'guest');

            Log::channel('security')->info('[Gate::after] Authorization decision', [
                'actor'   => $actor,
                'ability' => $ability,
                'result'  => $result ? 'ALLOW' : 'DENY',
            ]);

            return null; // preserve the original result
        });
    }
}
