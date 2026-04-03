<?php

namespace App\Http\Controllers;

use App\Exceptions\ProductOutOfStockException;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(): View
    {
        $userId = Auth::id();

        return view('cart.index', [
            'cart'       => $this->cartService->get($userId),
            'cartModels' => $this->cartService->getCartModels($userId),
            'total'      => $this->cartService->total($userId),
        ]);
    }

    public function add(int $productId)
    {
        $userId   = Auth::id();
        $quantity = (int) request('quantity', 1);
        try {
            $product = $this->cartService->add($userId, $productId, $quantity);
            return redirect()->back()->with('success', "{$product->name} added to cart.");
        } catch (ProductOutOfStockException) {
            return redirect()->back()->with('error', 'Product not found.');
        }
    }

    public function remove(int $productId)
    {
        $this->cartService->remove(Auth::id(), $productId);

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function decrement(int $productId)
    {
        $userId = Auth::id();
        $cart   = $this->cartService->get($userId);

        if (!isset($cart[$productId])) {
            return redirect()->back()->with('error', 'Product not found in cart.');
        }

        $this->cartService->decrement($userId, $productId);

        return redirect()->back()->with('success', 'Decreased quantity.');
    }

    public function clear()
    {
        $this->cartService->clear(Auth::id());

        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully.');
    }
}
