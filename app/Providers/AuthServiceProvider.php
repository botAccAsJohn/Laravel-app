<?php

namespace App\Providers;

use App\Models\{Admin, Order, Permission, Product, User, Review};
use App\Policies\{OrderPolicy, ProductPolicy, ReviewPolicy, UserPolicy};
use Illuminate\Auth\Access\Response;
use Illuminate\Support\Facades\{Auth, Gate, Log, Schema};
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // ── Gate::before() ── super-admin bypass ──────────────────────────────────
        Gate::before(function (User|Admin|null $user, string $ability) {
            if (is_impersonating()) {
                return null;
            }

            $adminUser = Auth::guard('admin')->user();
            if ($adminUser instanceof Admin) {
                $superEmails = array_map(
                    'trim',
                    explode(',', config('auth.super_admin_emails', ''))
                );
                if (in_array($adminUser->email, array_filter($superEmails), true)) {
                    Log::channel('security')->info('[Gate::before] Super-admin bypass', [
                        'admin_email' => $adminUser->email,
                        'ability' => $ability,
                    ]);
                    return true;
                }
            }
            return null;
        });

        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('edit-comment', fn (User $u,Review $r, Product $p) => $u->id === $r->user_id || $u->id ===$p->user_id);


        // ── Dynamic Permissions Gates ──
        if (Schema::hasTable('permissions')) {
            Permission::all()->each(function (Permission $permission) {
                Gate::define($permission->name, function ($user) use ($permission) {
                    if ($user instanceof Admin) {
                        return true;
                    }

                    return method_exists($user, 'hasPermission') ? $user->hasPermission($permission->name) : false;
                });
            });
        }

        // ── Gate::after() ── full audit log (every ability) ─────────────────────
        Gate::after(function (User|Admin|null $user, string $ability, bool|Response|null $result, mixed $arguments) {
            $adminUser = Auth::guard('admin')->user();
            $actor = $adminUser
                ? "Admin:{$adminUser->email}"
                : ($user ? "User:{$user->email}" : 'guest');

            Log::channel('security')->info('[Gate::after] Authorization decision', [
                'actor' => $actor,
                'ability' => $ability,
                'result' => $result ? 'ALLOW' : 'DENY',
            ]);

            return null; // never change the result — only observe
        });
    }
}
