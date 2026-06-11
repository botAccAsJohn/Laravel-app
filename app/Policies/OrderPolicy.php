<?php

namespace App\Policies;

use App\Models\Admin;
use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\{HandlesAuthorization, Response};

class OrderPolicy
{
    use HandlesAuthorization;

    public function before(User|Admin|null $user, string $ability): ?bool
    {
        if (is_admin()) {
            return true;
        }
        return null;
    }
    
    public function create(User|Admin|null $user): bool
    {
        return $user instanceof User && $user->hasPermission('place_order');
    }

    public function viewAny(User|Admin|null $user): bool
    {
        if (is_null($user)) {
            return false;
        }
        return $user->can('manage_orders');    
    }

    public function view(User|Admin|null $user, Order $order): Response|bool
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }
        if ($user->can('manage_orders')) {
            return true;
        }
        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();    
    }
    public function update(User|Admin|null $user, Order $order): Response|bool
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Can the user delete an order?
     * Admins only — granted entirely by before(). Customers: never.
     */
    public function delete(User|Admin|null $user, Order $order): Response|bool
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? Response::allow()
            : Response::denyAsNotFound();
    }

    /**
     * Can the user cancel an order?
     * Admins: handled by before(). Customers: only their own order.
     */
    public function cancel(User|Admin|null $user, Order $order): bool
    {
        if (is_null($user)) {
            return false;
        }

        if ($user->can('manage_orders')) {
            return true;
        }

        return ($user instanceof User && $user->hasRole('customer') && $order->user_id === $user->id)
            ? true
            : false;    
    }

    public function restore(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(Admin|User|null $user, Order $order)
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }

        if ($user->can('manage_orders')) {
            return Response::allow();
        }

        return Response::denyAsNotFound();
    }
}
