<?php

namespace App\Policies;

use App\Models\{Admin, User};
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class UserPolicy
{
    use HandlesAuthorization;

    public function viewAny(User|Admin|null $user): bool
    {
        return is_admin();
    }

    public function view(Admin|User|null $currentUser, User $model): bool
    {
        if(is_null($currentUser)){
            return false;
        }
        if($currentUser instanceof Admin || (method_exists($currentUser, 'hasRole') && $currentUser->hasRole('admin'))){
            return true;
        }
        return $currentUser->can('manage_users') || $currentUser->id === $model->id;
    }

    public function update(User|Admin|null $user, User $model): bool
    {
        if (is_admin()) {
            return true;
        }
        return $user instanceof User && $user->id === $model->id;
    }


    public function delete(User|Admin|null $user, User $model): bool
    {
        return is_admin();
    }

    public function restore(User|Admin|null $user, User $model): bool
    {
        return is_admin();
    }

    public function forceDelete(User|Admin|null $user, User $model): bool
    {
        return is_admin();
    }
}
