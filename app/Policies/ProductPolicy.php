<?php

namespace App\Policies;

use App\Models\{Admin, Product, User};
use Illuminate\Auth\Access\{HandlesAuthorization, Response};

class ProductPolicy
{
    use HandlesAuthorization;

    public function before(User|Admin|null $user, string $ability): ?bool
    {
        if (is_admin()) {
            return true;
        }
        return null;
    }

    public function viewAny(User|Admin|null $user): bool
    {
        return true;
    }

    public function view(User|Admin|null $user, Product $product): bool
    {
        return true;
    }

    public function create(User|Admin|null $user): bool
    {
        return $this->canAccess($user);  
    }

    public function update(User|Admin|null $user, Product $product): bool
    {
        return $this->canAccess($user);  
    }

    public function delete(User|Admin|null $user, Product $product): Response|bool
    {
        return $this->canAccess($user);  
    }

    public function restore(User|Admin|null $user, Product $product): bool
    {
        return $this->canAccess($user);  
    }

    public function forceDelete(User|Admin|null $user, Product $product): bool
    {
        return $this->canAccess($user);  
    }

    // public function waitlist(User|Admin|null $user, Product $product): bool
    // {
    //     if (is_null($user)) {
    //         return false;
    //     }
    //     if ($user instanceof Admin) {
    //         return false;
    //     }
    //     return $user->hasRole('customer')
    //         && $product->stock <= 0;
    // }
    private function canAccess(User|Admin|null $user): bool
    {
        if (is_null($user)) {
            return false;
        }
        return $user->can('manage_products');
    }
}
