<?php
// app/Policies/OrderPolicy.php
//
// Exercise 50.2 — Policy for the Order model.
//
// Key business rule:
//   • Customers can view and cancel ONLY their own orders.
//   • Admins (admin guard) can view, update, and delete any order.
//   • Creating an order is a customer action (from cart checkout).
//
// This replaces the private authorizeAccess() helper in OrderController
// with a proper named policy that can be re-used in API controllers and
// referenced in Blade with @can('view', $order).

namespace App\Policies;

use App\Models\{Order, User};
use Illuminate\Auth\Access\{HandlesAuthorization, Response};
use Illuminate\Support\Facades\Auth;

class OrderPolicy
{
    use HandlesAuthorization;

    private function isAdmin(): bool
    {
        return Auth::guard('admin')->check();
    }

    // ── Exercise 50.3: Policy before() ───────────────────────────────────────
    // Admins get unrestricted access to all order operations without evaluating
    // any individual policy method. Returning null falls through to the method.
    public function before(User $user, string $ability): ?bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * Admins see all orders; customers see only their own list.
     * The controller still adds the where('user_id') scope —
     * this gate governs access to the index endpoint itself.
     */
    public function viewAny(User $user): bool
    {
        return true; // all authenticated users may hit the index
    }

    /**
     * Admins can view any order.
     * Customers can view only their own.
     *
     * Returns 404 (not 403) for unauthorized access to prevent order-ID
     * enumeration — an attacker cannot tell whether order #1234 exists
     * or simply belongs to someone else.
     */
    public function view(User $user, Order $order): Response|bool
    {
        if ($this->isAdmin()) {
            return Response::allow();
        }

        if ($order->user_id === $user->id) {
            return Response::allow();
        }

        // The order exists but belongs to another customer — return 404.
        return Response::denyAsNotFound('Order not found.');
    }

    /**
     * Creating orders is a customer action (from the checkout flow).
     */
    public function create(User $user): bool
    {
        return ! $this->isAdmin(); // customers only
    }

    /**
     * Only admins may edit order status, addresses, etc.
     */
    public function update(User $user, Order $order): bool
    {
        return $this->isAdmin();
    }

    /**
     * Customers can cancel their own orders if status allows it.
     * Admins can cancel any order.
     *
     * Note: the status check (pending/confirmed) is enforced in the
     * controller, not here — policy answers "who", controller answers "when".
     */
    public function cancel(User $user, Order $order): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $order->user_id === $user->id;
    }

    /**
     * Only admins may hard-delete orders.
     */
    public function delete(User $user, Order $order): bool
    {
        return $this->isAdmin();
    }

    /**
     * Restore soft-deleted orders. Admins only.
     */
    public function restore(User $user, Order $order): bool
    {
        return $this->isAdmin();
    }

    /**
     * Permanently purge an order. Admins only.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $this->isAdmin();
    }
}
