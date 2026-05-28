<?php
// app/Http/Controllers/ReviewController.php
// Exercise 50.2 — Added update() and destroy() with ReviewPolicy authorization.

namespace App\Http\Controllers;

use App\Models\{Product, Review};
use App\Events\Behavior\ProductReviewed;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\{Auth, RateLimiter, Log};

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request, Product $product): RedirectResponse
    {
        $this->authorize('create', Review::class);

        $validatedData = $request->validate([
            'rating'      => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        $review = new Review([
            'rating'      => $validatedData['rating'],
            'review_text' => $validatedData['review_text'],
            'product_id'  => $product->id,
            'user_id'     => Auth::id(),
        ]);

        $user = $request->user();
        $key  = 'reviews:' . ($user ? $user->id : $request->ip());

        // Check if rate limited
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            Log::channel('security')->warning('Rate limit hit: reviews submission (tooManyAttempts)', [
                'ip'                   => $request->ip(),
                'user_id'              => $user?->id,
                'available_in_seconds' => $seconds,
            ]);

            return redirect()->back()->withErrors([
                'review_text' => "You have submitted too many reviews. Please try again in {$minutes} minutes ({$seconds} seconds).",
            ]);
        }

        $saved = RateLimiter::attempt(
            $key,
            5,
            function () use ($review) {
                return $review->save();
            },
            3600 // 1 hour
        );

        if (! $saved) {
            $seconds = RateLimiter::availableIn($key);
            $minutes = ceil($seconds / 60);

            Log::channel('security')->warning('Rate limit hit: reviews submission (attempt failed)', [
                'ip'                   => $request->ip(),
                'user_id'              => $user?->id,
                'available_in_seconds' => $seconds,
            ]);

            return redirect()->back()->withErrors([
                'review_text' => "You have submitted too many reviews. Please try again in {$minutes} minutes ({$seconds} seconds).",
            ]);
        }

        event(new ProductReviewed($review));

        return redirect()->back()->with('success', 'Your review has been submitted successfully.');
    }

    /**
     * Update a review.
     *
     * ReviewPolicy::update() enforces:
     *   1. Caller is the review author.
     *   2. Review was created within the last 24 hours.
     */
    public function update(Request $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validate([
            'rating'      => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return redirect()->back()->with('success', 'Your review has been updated.');
    }

    /**
     * Delete a review.
     *
     * ReviewPolicy::delete() allows:
     *   • The review author within 24 hours.
     *   • Admins at any time (for moderation).
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->delete();

        return redirect()->back()->with('success', 'Review removed.');
    }
}
