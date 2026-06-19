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
        if ($user instanceof Admin) {
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
        return !is_null($user);
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
        return false;
    }

    public function delete(User|Admin|null $user, Order $order): Response|bool
    {
        if (is_null($user)) {
            return Response::denyAsNotFound();
        }
        if ($user->can('manage_orders')) {
            return Response::allow();
        }
        return false;
    }

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
