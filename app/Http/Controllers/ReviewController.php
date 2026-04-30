<?php

namespace App\Http\Controllers;

use App\Models\{Product, Review};
use App\Events\Behavior\ProductReviewed;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Store a newly created review in storage.
     */
    public function store(Request $request, Product $product)
    {
        $validatedData = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review_text' => 'nullable|string|max:1000',
        ]);

        $review = new Review([
            'rating' => $validatedData['rating'],
            'review_text' => $validatedData['review_text'],
            'product_id' => $product->id,
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
        ]);

        $review->save();

        // Dispatch the event to update the product's average rating
        event(new ProductReviewed($review));

        return redirect()->back()->with('success', 'Your review has been submitted successfully.');
    }
}
