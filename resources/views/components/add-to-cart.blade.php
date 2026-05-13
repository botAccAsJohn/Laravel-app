<?php

use Livewire\Component;
use App\Models\Product;
use App\Events\Behavior\ProductAddToCart;
use App\Exceptions\ProductOutOfStockException;
use Illuminate\Support\Facades\Auth;

new class extends Component
{
    public Product $product;

    public function addToCart()
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $userId = Auth::id();
        $productId = $this->product->id;
        $quantity = 1;

        try {
            event(new ProductAddToCart($userId, $productId, $quantity));
            $this->js("
                Toastify({
                    text: '✅ Added to cart successfully',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        borderRadius: '12px',
                        boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)'
                    }
                }).showToast();
            ");
            $this->dispatch('cart-updated');
        } catch (ProductOutOfStockException) {
            $this->js("
                Toastify({
                    text: '❌ Product out of stock',
                    duration: 3000,
                    gravity: 'top',
                    position: 'right',
                    style: {
                        background: 'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)',
                        borderRadius: '12px',
                        boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)'
                    }
                }).showToast();
            ");
        }
    }
};
?>

<div>
    @if(!$product->is_active || $product->quantity <= 0)
        <button type="button" disabled
            class="h-12 w-12 flex items-center justify-center bg-slate-300 text-white rounded-2xl cursor-not-allowed shadow-none"
            title="Out of Stock">
            <svg class="w-5 h-5 opacity-60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    @else
        <button type="button" wire:click.prevent="addToCart"
            class="h-12 w-12 flex items-center justify-center bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl shadow-lg transition-all duration-300 hover:rotate-6 active:scale-90 cursor-pointer group/btn"
            title="Add to Cart">
            <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
        </button>
    @endif
</div>