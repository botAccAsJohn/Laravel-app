<?php
// app/Policies/ReviewPolicy.php
//
// Exercise 50.2 — Policy for the Review model.
//
// Business rules:
//   • Any authenticated customer can create a review.
//   • Only the review author can update or delete their review.
//   • The 24-hour edit window: updates/deletes are only allowed within 24h
//     of the review's created_at timestamp. After that it is permanent.
//   • Admins can delete any review for moderation purposes.

namespace App\Policies;

use App\Models\{Review, User};
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Auth;

class ReviewPolicy
{
    use HandlesAuthorization;

    private function isAdmin(): bool
    {
        return Auth::guard('admin')->check() && !is_impersonating();
    }

    /**
     * Any authenticated customer can submit a review.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the review author can update their review, and only within 24 hours.
     *
     * The 24-hour window prevents review manipulation after a product's
     * reputation has been established — a common abuse pattern.
     */
    public function update(User $user, Review $review): bool
    {
        if ($review->user_id !== $user->id) {
            return false;
        }

        // Enforce the 24-hour edit window.
        return $review->created_at->gt(now()->subHours(24));
    }

    /**
     * The review author can delete within 24 hours.
     * Admins can delete any review (moderation).
     */
    public function delete(User $user, Review $review): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($review->user_id !== $user->id) {
            return false;
        }

        return $review->created_at->gt(now()->subHours(24));
    }

    /**
     * Viewing individual reviews — anyone can see them (public).
     */
    public function view(?User $user, Review $review): bool
    {
        return true;
    }

    public function viewAny(?User $user): bool
    {
        return true;
    }
}
