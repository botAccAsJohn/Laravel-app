<?php

namespace App\Http\Controllers;

use App\Services\RecentlyViewServices;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecentlyViewController extends Controller
{
    public function __construct(private RecentlyViewServices $service) {}

    /**
     * Display the recently viewed products page.
     */
    public function index(): View
    {
        $key = $this->service->resolveRecentlyViewedKey();
        $products = $this->service->getRecentlyViewedModels($key);

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
        $key = $this->service->resolveRecentlyViewedKey();
        $this->service->clear($key);

        return redirect()->route('recently.index')->with('success', 'Recently viewed history cleared.');
    }

    /**
     * Record a product view manually.
     */
    public function add(int $productId)
    {
        $key = $this->service->resolveRecentlyViewedKey();
        $this->service->record($key, $productId);

        return response()->json(['success' => true]);
    }
}
