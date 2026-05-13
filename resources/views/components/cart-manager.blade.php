<?php

use Livewire\Component;
use App\Services\CartService;
use App\Events\Behavior\ProductAddToCart;
use App\Exceptions\ProductOutOfStockException;
use Illuminate\Support\Facades\Auth;

use Livewire\Attributes\Computed;

new class extends Component
{
    #[Computed]
    public function cartData()
    {
        $userId = Auth::id();
        $cartService = app(CartService::class);
        $rawCart = $cartService->get($userId) ?? [];

        // Filter out metadata keys starting with _ to avoid errors in loops
        $cart = array_filter($rawCart, fn($k) => !str_starts_with($k, '_'), ARRAY_FILTER_USE_KEY);

        return [
            'cart'       => $cart,
            'cartModels' => $cartService->getCartModels($userId) ?? [],
            'total'      => $cartService->total($userId) ?? 0,
        ];
    }

    public function increment($productId)
    {
        try {
            event(new ProductAddToCart(Auth::id(), $productId, 1));
            $this->showToast('✅ ' . __('cart.toast_qty_increased'), 'success');
        } catch (ProductOutOfStockException) {
            $this->showToast('❌ ' . __('cart.toast_out_of_stock'), 'error');
        }
    }

    public function decrement($productId)
    {
        app(CartService::class)->decrement(Auth::id(), $productId);
        $this->showToast('✅ ' . __('cart.toast_qty_decreased'), 'success');
    }

    public function remove($productId)
    {
        app(CartService::class)->remove(Auth::id(), $productId);
        $this->showToast('🗑️ ' . __('cart.toast_removed'), 'success');
    }

    public function clear()
    {
        app(CartService::class)->clear(Auth::id());
        $this->showToast('🗑️ ' . __('cart.toast_cleared'), 'success');
    }

    private function showToast($message, $type)
    {
        $bg = $type === 'success' ? 'linear-gradient(135deg, #10b981 0%, #059669 100%)' : 'linear-gradient(135deg, #ef4444 0%, #b91c1c 100%)';
        $this->js("
            Toastify({
                text: '{$message}',
                duration: 3000,
                gravity: 'top',
                position: 'right',
                style: {
                    background: '{$bg}',
                    borderRadius: '12px',
                    boxShadow: '0 10px 15px -3px rgba(0,0,0,0.1)'
                }
            }).showToast();
        ");
        $this->dispatch('cart-updated');
    }
};
?>

<div class="bg-gray-100 min-h-screen py-8" wire:key="cart-manager-container">
    @php
        $cartData = $this->cartData;
        $cart = $cartData['cart'];
        $cartModels = $cartData['cartModels'];
        $total = $cartData['total'];
    @endphp
    <div class="max-w-7xl mx-auto px-4 flex flex-col lg:flex-row gap-6">

        {{-- LEFT COLUMN: CART ITEMS --}}
        <div class="w-full {{ empty($cart) ? 'max-w-4xl mx-auto py-10' : 'lg:w-3/4' }} flex flex-col gap-4">
            <div class="bg-white p-6 shadow-sm">
                <div class="flex justify-between items-end border-b pb-2 mb-4">
                    <h1 class="text-3xl font-medium text-gray-900">{{ __('Shopping Cart') }}</h1>
                    <span class="text-gray-500 text-sm hidden sm:block">{{ __('Price') }}</span>
                </div>

                @empty($cart)
                    <div class="py-16 mt-12 text-center text-gray-600 flex flex-col items-center">
                        <div class="mb-6 p-4 bg-gray-50 rounded-full border border-gray-100 shadow-inner text-gray-300">
                            <svg class="h-20 w-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11V8m0 0L10 10m2-2l2 2" />
                            </svg>
                        </div>
                        <p class="text-2xl font-semibold text-gray-900 mb-2">{{ __('cart.empty_title') }}</p>
                        <p class="text-gray-500 mb-8 max-w-xs px-4 mx-auto">{{ __('cart.empty_desc') }}</p>
                        <a href="{{ route('products.index') }}" class="inline-flex items-center px-8 py-3 bg-blue-600 text-white font-bold rounded-full hover:bg-blue-700 shadow-md transition-all duration-300 hover:-translate-y-1">
                            {{ __('cart.explore_products') }}
                        </a>
                    </div>
                @else
                    @foreach ($cart as $item)
                        @php
                            $model = $cartModels[$item['id']] ?? null;
                        @endphp
                        <div class="flex flex-col sm:flex-row py-4 border-b gap-4" wire:key="cart-item-{{ $item['id'] }}">
                            
                            {{-- Product Image --}}
                            <div class="sm:w-1/4 md:w-1/5 flex-shrink-0">
                                @if($model)
                                    <img src="{{ $model->image_url }}" alt="{{ $item['name'] }}" class="w-full h-auto object-contain max-h-48">
                                @else
                                    <div class="w-full h-32 bg-gray-200 flex items-center justify-center text-gray-500 rounded">No Image</div>
                                @endif
                            </div>

                            {{-- Product Details --}}
                            <div class="sm:w-full md:w-3/5 flex flex-col">
                                <a href="{{ $model ? route('products.show', $model->slug) : '#' }}" class="text-lg font-medium text-blue-900 hover:underline mb-1">
                                    {{ $item['name'] }}
                                </a>
                                <p class="text-sm text-green-700 mb-1">{{ __('cart.in_stock') }}</p>
                                <p class="text-xs text-gray-500 mb-2">{{ __('cart.free_shipping_eligible') }}</p>
                                
                                <div class="flex items-center text-xs text-gray-700 mb-3">
                                    <input type="checkbox" class="mr-1 h-3 w-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>{{ __('cart.gift_checkbox') }}</span>
                                </div>

                                {{-- Actions row --}}
                                <div class="flex flex-wrap items-center gap-4 text-sm mt-auto">
                                    
                                    {{-- Quantity pill --}}
                                    <div class="flex items-center border border-gray-300 rounded-full bg-gray-50 overflow-hidden shadow-sm">
                                        <button wire:click="decrement({{ $item['id'] }})" class="border-r border-gray-300 hover:bg-gray-200 transition px-3 py-1 flex items-center justify-center text-gray-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                        </button>
                                        <div class="px-3 font-semibold text-gray-800">{{ $item['quantity'] }}</div>
                                        <button wire:click="increment({{ $item['id'] }})" class="border-l border-gray-300 hover:bg-gray-200 transition px-3 py-1 flex items-center justify-center text-gray-600">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        </button>
                                    </div>

                                    <div class="text-gray-300">|</div>

                                    <button wire:click="remove({{ $item['id'] }})" class="text-teal-700 hover:underline">{{ __('Remove') }}</button>

                                    <div class="text-gray-300">|</div>

                                    <a href="#" class="text-teal-700 hover:underline">{{ __('cart.save_later') }}</a>
                                    
                                    <div class="text-gray-300">|</div>

                                    <a href="#" class="text-teal-700 hover:underline">{{ __('cart.share') }}</a>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="sm:w-1/4 md:w-1/5 text-right mt-2 sm:mt-0">
                                @php
                                    $effectivePrice = ($model && $model->discount_price)
                                        ? (float) $model->discount_price
                                        : (float) $item['price'];
                                    $lineTotal = $effectivePrice * $item['quantity'];
                                @endphp

                                @if($model && $model->discount_price)
                                    <div class="text-xs text-gray-400 line-through">@currency($item['price'])</div>
                                    <div class="font-bold text-lg text-green-600">@currency($model->discount_price)</div>
                                    <div class="text-xs text-green-700 font-medium">
                                        {{ round((1 - $model->discount_price / $item['price']) * 100) }}% off
                                    </div>
                                @else
                                    <span class="font-bold text-lg text-gray-900">@currency($item['price'])</span>
                                @endif

                                @if($item['quantity'] > 1)
                                    <div class="text-xs text-gray-500 mt-1">× {{ $item['quantity'] }} = @currency($lineTotal)</div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end pt-4 mt-2">
                        <p class="text-lg">
                            {{ trans_choice('cart.subtotal', count($cart), ['count' => count($cart)]) }}
                            <span class="font-bold text-gray-900">@currency($total)</span>
                        </p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button wire:click="clear()" wire:confirm="{{ __('cart.clear_cart_confirm') }}" class="text-red-500 hover:underline text-sm">{{ __('cart.clear_cart') }}</button>
                    </div>
                @endempty
            </div>
            
            <div class="text-xs text-gray-500 mb-6">
                {{ __('cart.disclaimer') }}
            </div>
        </div>

        {{-- RIGHT COLUMN: CHECKOUT --}}
        @if(!empty($cart))
            <div class="w-full lg:w-1/4">
                <div class="bg-white p-6 shadow-sm mb-4">
                    
                    {{-- Free Delivery Promo --}}
                    <div class="mb-4">
                        <div class="flex items-center gap-2 text-green-700 mb-1">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            <span class="text-sm font-bold">{{ __('cart.free_delivery_eligible') }}</span>
                        </div>
                        <p class="text-xs text-gray-600 pl-7">{!! __('cart.free_delivery_checkout') !!}</p>
                    </div>

                    <p class="text-lg mb-2">
                        {{ trans_choice('cart.subtotal', count($cart), ['count' => count($cart)]) }}
                        <span class="font-bold text-gray-900">@currency($total)</span>
                    </p>
                    
                    <div class="flex items-center text-sm text-gray-700 mb-4">
                        <input type="checkbox" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>{{ __('cart.gift_order') }}</span>
                    </div>

                    <a href="{{ route('orders.create') }}"
                       class="block w-full text-center bg-yellow-400 hover:bg-yellow-500 shadow-sm border border-yellow-500 rounded-full py-2 px-4 text-sm text-gray-900 transition mb-4">
                        {{ __('Proceed to Buy') }}
                    </a>

                    <div class="border border-gray-200 rounded p-2 text-sm text-gray-800 flex justify-between items-center bg-gray-50 cursor-pointer shadow-sm">
                        <span>{{ __('cart.emi_available') }}</span>
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
