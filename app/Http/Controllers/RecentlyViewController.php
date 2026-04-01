<?php

namespace App\Http\Controllers;

use App\Services\RecentlyViewServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RecentlyViewController extends Controller
{
    public function __construct(private RecentlyViewServices $service) {}

    /**
     * Display the recently viewed products page.
     */
    public function index(): View
    {
        $products = $this->service->getRecentlyViewedModels(Auth::id());

        return view('recently.index', [
            'products'   => $products,
            'page_title' => 'Recently Viewed Products',
        ]);
    }

    /**
     * Clear the recently viewed history for the current user.
     */
    public function clear(Request $request)
    {
        $this->service->clear(Auth::id());

        return redirect()->route('recently.index')->with('success', 'Recently viewed history cleared.');
    }

    /**
     * Record a product view manually.
     */
    public function add(int $productId)
    {
        if (Auth::check()) {
            $this->service->record(Auth::id(), $productId);
        }

        return response()->json(['success' => true]);
    }
}
