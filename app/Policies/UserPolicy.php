<?php
// app/Policies/UserPolicy.php
//
// Exercise 50.2 — Policy for the User model.
//
// Business rules:
//   • Admins can view any user profile (user management).
//   • Users can only view and update their own profile.
//   • Only admins can delete users.

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    use HandlesAuthorization;

    private function isAdmin(): bool
    {
        return Auth::guard('admin')->check() && !is_impersonating();
    }

    /**
     * Admins can list all users; customers cannot.
     */
    public function viewAny(User $user): bool
    {
        return $this->isAdmin();
    }

    /**
     * Admins can view any user.
     * Customers can only view their own profile.
     */
    public function view(User $user, User $model): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $user->id === $model->id;
    }

    /**
     * Admins can update any user; customers can only update themselves.
     */
    public function update(User $user, User $model): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return $user->id === $model->id;
    }

    /**
     * Only admins can delete user accounts.
     */
    public function delete(User $user, User $model): bool
    {
        return $this->isAdmin();
    }

    /**
     * Only admins can restore soft-deleted users.
     */
    public function restore(User $user, User $model): bool
    {
        return $this->isAdmin();
    }

    /**
     * Only admins can permanently purge a user.
     */
    public function forceDelete(User $user, User $model): bool
    {
        return $this->isAdmin();
    }
}
