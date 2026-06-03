<?php
// app/Policies/ProductPolicy.php
//
// Exercise 50.2 — Policy for the Product model.
//
// Auto-discovered by Laravel via the convention:
//   App\Models\Product  →  App\Policies\ProductPolicy
//
// Who can do what:
//   viewAny     → everyone (guest or customer) — public product catalogue
//   view        → everyone — public product detail
//   create      → admins only
//   update      → admins only
//   delete      → admins only (soft-delete)
//   restore     → admins only
//   forceDelete → admins only (permanent purge)

namespace App\Policies;

use App\Models\{Admin, Product, User};
use Illuminate\Auth\Access\{HandlesAuthorization, Response};
use Illuminate\Support\Facades\Auth;

class ProductPolicy
{
    use HandlesAuthorization;

    // ── Helper — is the current admin-guard user authenticated? ──────────────
    // The web-guard $user parameter is irrelevant for admin-only checks because
    // admins authenticate through a separate guard and never populate $user.
    private function isAdmin(): bool
    {
        return Auth::guard('admin')->check() && !is_impersonating();
    }

    // ── Exercise 50.3: Policy before() — super-admin bypass ──────────────────
    //
    // before() runs ahead of EVERY individual policy method. When it returns a
    // non-null value that value is the final authorization result — the method
    // (create, update, delete …) is never called.
    //
    // Returning null falls through to the specific method. This is identical in
    // behaviour to Gate::before() but scoped to this one model's policy.
    //
    // NOTE: before() also receives ?User, so the same dual-guard check applies.
    public function before(User|Admin|null $user, string $ability): ?bool
    {
        // Any authenticated admin gets unrestricted access to all product operations.
        if ($this->isAdmin()) {
            return true;
        }

        return null; // fall through to the specific method
    }

    /**
     * View the product listing page.
     * Public — guests may browse the catalogue.
     */
    public function viewAny(User|Admin|null $user): bool
    {
        return true;
    }

    /**
     * View a single product detail page.
     * Public — guests may view any product.
     */
    public function view(User|Admin|null $user, Product $product): bool
    {
        return true;
    }

    /**
     * Create a new product. Admins only.
     */
    public function create(User|Admin|null $user): bool
    {
        return $this->isAdmin();
    }

    /**
     * Update any existing product. Admins only.
     */
    public function update(User|Admin|null $user, Product $product): bool
    {
        return $this->isAdmin();
    }

    /**
     * Soft-delete a product. Admins only.
     *
     * Uses Response::denyAsNotFound() so that attempting to delete a product
     * the caller cannot see returns HTTP 404 rather than 403. This prevents
     * leaking the existence of products that are hidden or admin-only.
     */
    public function delete(User|Admin|null $user, Product $product): Response|bool
    {
        if ($this->isAdmin()) {
            return Response::allow();
        }

        // Non-admin trying to delete: return 404 not 403.
        // 403 would reveal that the resource EXISTS but is forbidden.
        // 404 gives the attacker no information — the product "doesn't exist".
        return Response::denyAsNotFound('Product not found.');
    }

    /**
     * Restore a soft-deleted product. Admins only.
     */
    public function restore(User|Admin|null $user, Product $product): bool
    {
        return $this->isAdmin();
    }

    /**
     * Permanently delete a product from the database. Admins only.
     */
    public function forceDelete(User|Admin|null $user, Product $product): bool
    {
        return $this->isAdmin();
    }
}
