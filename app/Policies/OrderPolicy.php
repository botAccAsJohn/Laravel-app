<?php
// app/Policies/OrderPolicy.php
//
// Policy for the Order model.
//
// This project uses TWO authentication guards:
//   • web   → App\Models\User  (customers)
//   • admin → App\Models\Admin (administrators)
//
// Laravel's Gate resolves $user from the CURRENTLY AUTHENTICATED model across
// all guards. When an Admin is logged in, the Gate passes the Admin instance —
// so every method must accept User|Admin|null (the same union used throughout
// AuthServiceProvider). Using ?User alone causes a TypeError when Admin is given.
//
// Admin checks are performed via Auth::guard('admin')->check() in isAdmin(),
// not by inspecting the $user argument (which may be an Admin or null).
//
// Who can do what:
//   viewAny  → authenticated customers (own orders); admins via before()
//   view     → customers see only their own order; admins via before()
//   update   → admins only (via before())
//   delete   → admins only (via before())
//   cancel   → customers (own order) OR admins (via before())

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class OrderPolicy
{
    use HandlesAuthorization;

    // ── Helper — is the current admin-guard user authenticated? ──────────────
    // We check the guard explicitly rather than relying on the $user argument
    // because $user may be a User, Admin, or null depending on which guard
    // is active. Auth::guard('admin')->check() is always unambiguous.
    private function isAdmin(): bool
    {
        return Auth::guard('admin')->check();
    }

    // ── before() — admin bypass ───────────────────────────────────────────────
    //
    // Runs ahead of every policy method. Returning non-null short-circuits the
    // specific method. Returning null falls through.
    //
    // IMPORTANT: must accept User|Admin|null — when the admin guard is active
    // Laravel passes an Admin instance, not a User. Typing as ?User causes a
    // TypeError ("Admin given").
    public function before(User|Admin|null $user, string $ability): ?bool
    {
        if ($this->isAdmin()) {
            return true; // admins may do anything with orders
        }

        return null; // fall through to the specific method
    }

    /**
     * Can the user see the orders listing?
     * Admins: handled by before(). Customers: yes if authenticated.
     * (The controller/service scopes the query to their own orders.)
     */
    public function viewAny(User|Admin|null $user): bool
    {
        return $user instanceof User;
    }

    /**
     * Can the user view a specific order?
     * Admins: handled by before(). Customers: only their own order.
     */
    public function view(User|Admin|null $user, Order $order): bool
    {
        return $user instanceof User && $user->id === $order->user_id;
    }

    /**
     * Can the user update an order (e.g. change status)?
     * Admins only — granted entirely by before(). Customers: never.
     */
    public function update(User|Admin|null $user, Order $order): bool
    {
        return false;
    }

    /**
     * Can the user delete an order?
     * Admins only — granted entirely by before(). Customers: never.
     */
    public function delete(User|Admin|null $user, Order $order): bool
    {
        return false;
    }

    /**
     * Can the user cancel an order?
     * Admins: handled by before(). Customers: only their own order.
     */
    public function cancel(User|Admin|null $user, Order $order): bool
    {
        return $user instanceof User && $user->id === $order->user_id;
    }
}
