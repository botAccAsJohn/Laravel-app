<?php

namespace App\Policies;

use App\Models\{Admin, Review, User};
use Illuminate\Auth\Access\HandlesAuthorization;

class ReviewPolicy
{
    use HandlesAuthorization;

    public function create(User|Admin|null $user): bool //authenticated customer can submit a review.
    {
        return $user instanceof User;
    }

    // Only the review author can update their review, and only within 24 hours.
    public function update(User|Admin|null $user, Review $review): bool
    {
        // Enforce the 24-hour edit window.
        return $user instanceof User && $review->user_id === $user->id && $review->created_at->gt(now()->subHours(24));
    }

    // The review author can delete within 24 hours.
    public function delete(User|Admin|null $user, Review $review): bool
    {
        if (is_Admin()) {
            return true;
        }
        if (! $user instanceof User || $review->user_id !== $user->id) {
            return false;
        }
        return $review->created_at->gt(now()->subHours(24));
    }

    public function view(User|Admin|null $user, Review $review): bool
    {
        return true;
    }

    public function viewAny(User|Admin|null $user): bool
    {
        return true;
    }
}
