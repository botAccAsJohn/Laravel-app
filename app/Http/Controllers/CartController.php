<?php

namespace App\Http\Controllers;

use App\Events\Behavior\ProductAddToCart;
use App\Exceptions\ProductOutOfStockException;
use App\Services\CartService;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private CartService $cartService) {}

    public function index(): View
    {
        $cartKey = $this->cartService->resolveCartKey();

        return view('cart.index', [
            'cart'       => $this->cartService->get($cartKey),
            'cartModels' => $this->cartService->getCartModels($cartKey),
            'total'      => $this->cartService->total($cartKey),
        ]);
    }

    public function add(int $productId)
    {
        $cartKey  = $this->cartService->resolveCartKey();
        $quantity = (int) request('quantity', 1);

        try {
            event(new ProductAddToCart($cartKey, $productId, $quantity));
            return redirect()->back()->with('success', 'Added to cart.');
        } catch (ProductOutOfStockException) {
            return redirect()->back()->with('error', 'Product not found or out of stock.');
        }
    }

    public function remove(int $productId)
    {
        $this->cartService->remove($this->cartService->resolveCartKey(), $productId);

        return redirect()->back()->with('success', 'Item removed from cart.');
    }

    public function decrement(int $productId)
    {
        $cartKey = $this->cartService->resolveCartKey();
        $cart    = $this->cartService->get($cartKey);

        if (! isset($cart[$productId])) {
            return redirect()->back()->with('error', 'Product not found in cart.');
        }

        $this->cartService->decrement($cartKey, $productId);

        return redirect()->back()->with('success', 'Decreased quantity.');
    }

    public function clear()
    {
        $this->cartService->clear($this->cartService->resolveCartKey());

        return redirect()->route('cart.index')->with('success', 'Cart cleared successfully.');
    }
}
