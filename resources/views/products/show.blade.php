@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Product Details</h1>
        <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900 border py-2 px-4 rounded">Back to List</a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">

        {{-- Product Image --}}
        @if($product->image_path)
            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}"
                 class="w-full h-64 object-cover rounded-lg mb-6 border border-gray-200">
        @else
            <div class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 mb-6 border border-gray-200">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
        @endif

        {{-- Name + Status --}}
        <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $product->name }}</h2>
        <div class="flex items-center gap-2 mb-4">
            <span class="text-xs font-bold uppercase tracking-widest border inline-block px-2.5 py-1 rounded-full
                {{ $product->is_active ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400' }}">
                {{ $product->is_active ? 'Active' : 'Inactive' }}
            </span>
            
            <span id="top-stock-badge" class="text-xs font-bold uppercase tracking-widest border inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full transition-all
                {{ $product->quantity > 0 ? 'bg-indigo-50 text-indigo-700 border-indigo-200' : 'bg-gray-100 text-gray-600 border-gray-300' }}">
                @if($product->quantity > 0)
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                    {{ $product->quantity }} In Stock
                @else
                    <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>
                    Out of Stock
                @endif
            </span>
        </div>

        {{-- Category --}}
        @if($product->category)
            <p class="text-sm text-gray-500 mb-3">
                Category: <span class="font-medium text-gray-700">{{ $product->category->name }}</span>
            </p>
        @endif

        {{-- Description --}}
        <p class="text-gray-700 mb-6 whitespace-pre-line">{{ $product->description }}</p>

        {{-- Pricing --}}
        <div class="mb-6">
            @if($product->discount_price)
                <div class="text-gray-400 line-through text-lg">₹{{ number_format($product->price, 2) }}</div>
                <div class="text-2xl font-bold text-green-600">₹{{ number_format($product->discount_price, 2) }}</div>
                <span class="text-xs bg-green-100 text-green-700 border border-green-300 px-2 py-0.5 rounded">
                    {{ round((1 - $product->discount_price / $product->price) * 100) }}% OFF
                </span>
            @else
                <div class="text-xl font-bold text-indigo-700">₹{{ number_format($product->price, 2) }}</div>
            @endif
        </div>

        {{-- Add to Cart Section --}}
        <div class="mb-8" id="product-action-section">
            <p id="stock-indicator" class="text-sm font-bold {{ $product->quantity > 0 ? 'text-green-600' : 'text-red-600' }} mb-2 transition-all">
                {{ $product->quantity > 0 ? "In Stock: {$product->quantity} available" : 'Out of Stock' }}
            </p>

            <div id="add-to-cart-wrapper" class="{{ $product->is_active && $product->quantity > 0 ? 'block' : 'hidden' }}">
                <form action="{{ route('cart.add', $product->id) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4">
                    @csrf
                    <div class="flex items-center justify-between border border-gray-300 rounded-lg overflow-hidden bg-white shadow-sm w-full sm:w-auto">
                        <button type="button" 
                                onclick="const q = document.getElementById('qty'); q.value = Math.max(1, parseInt(q.value)-1);" 
                                class="px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-gray-600 font-bold text-xl">-</button>
                        <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ $product->quantity }}"
                               class="w-16 text-center border-none bg-transparent focus:ring-0 font-semibold text-lg" readonly>
                        <button type="button" 
                                onclick="const q = document.getElementById('qty'); const max = parseInt(q.max) || 999; if(parseInt(q.value) < max) q.value = parseInt(q.value)+1;" 
                                class="px-4 py-3 bg-gray-50 hover:bg-gray-100 transition text-gray-600 font-bold text-xl">+</button>
                    </div>
                    
                    <button type="submit" 
                            class="flex-1 bg-gradient-to-r from-yellow-400 to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-gray-900 font-bold py-4 px-8 rounded-lg shadow-md transition-all duration-300 transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                        <span class="text-lg">Add to Cart</span>
                    </button>
                </form>
                <p class="text-xs text-gray-500 mt-3 flex items-center gap-1">
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    Secure transaction • Eligible for FREE Shipping
                </p>
            </div>

            <div id="out-of-stock-wrapper" class="{{ !$product->is_active || $product->quantity <= 0 ? 'block' : 'hidden' }}">
                <div class="p-4 bg-red-50 text-red-700 border border-red-200 rounded-lg flex items-center gap-3 shadow-sm">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <span id="unavailable-text" class="font-semibold px-4">
                        {{ $product->is_active ? 'This product is currently out of stock.' : 'This product is currently unavailable.' }}
                    </span>
                </div>
            </div>
        </div>

        {{-- Tags --}}
        @if($product->tags && count($product->tags))
            <div class="flex flex-wrap gap-2 mb-6">
                @foreach($product->tags as $tag)
                    <span class="text-xs bg-indigo-100 text-indigo-700 border border-indigo-300 px-2 py-1 rounded-full">
                        {{ $tag }}
                    </span>
                @endforeach
            </div>
        @endif

        {{-- Admin actions --}}
        @auth
        @if(auth()->user()->role === 'admin')
        <div class="flex space-x-4 border-t pt-4">
            <a href="{{ route('products.edit', $product->slug) }}"
               class="bg-yellow-500 text-white py-2 px-4 rounded hover:bg-yellow-600 transition">Edit Product</a>
            <form action="{{ route('products.destroy', $product->slug) }}" method="POST"
                  onsubmit="return confirm('Are you sure you want to delete this product?');">
                @csrf
                @method('DELETE')
                <button type="submit" class="bg-red-600 text-white py-2 px-4 rounded hover:bg-red-700 transition">Delete Product</button>
            </form>
        </div>
        @endif
        @endauth

    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    document.addEventListener('DOMContentLoaded', function () {
        let echoRetries = 0;
        
        function initStockEcho() {
            if (window.Echo) {
                console.log('Echo initialized, subscribing to: product.{{ $product->id }}');
                window.Echo.channel('product.{{ $product->id }}')
                    .listen('.stock.changed', function (e) {
                        const newStock = e.new_stock;
                        console.log('Stock Update Received via Echo:', newStock, e);
                        
                        const indicator = document.getElementById('stock-indicator');
                        const topBadge = document.getElementById('top-stock-badge');
                        
                        const qtyInput = document.getElementById('qty');
                        const cartWrapper = document.getElementById('add-to-cart-wrapper');
                        const outOfStockWrapper = document.getElementById('out-of-stock-wrapper');
                        const outOfStockText = document.getElementById('unavailable-text');
                        
                        if (indicator) {
                            if (newStock > 0) {
                                indicator.textContent = `In Stock: ${newStock} available`;
                                indicator.className = 'text-sm font-bold text-green-600 mb-2 transition-all';
                                
                                if (topBadge) {
                                    topBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span> ${newStock} In Stock`;
                                    topBadge.className = 'text-xs font-bold uppercase tracking-widest border inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full transition-all bg-indigo-50 text-indigo-700 border-indigo-200';
                                }
                                
                                if (qtyInput) {
                                    qtyInput.max = newStock;
                                    if (parseInt(qtyInput.value) > newStock) {
                                        qtyInput.value = newStock;
                                    }
                                }
                                
                                @if($product->is_active)
                                    if (cartWrapper) {
                                        cartWrapper.classList.remove('hidden');
                                        cartWrapper.classList.add('block');
                                    }
                                    if (outOfStockWrapper) {
                                        outOfStockWrapper.classList.remove('block');
                                        outOfStockWrapper.classList.add('hidden');
                                    }
                                @endif
                            } else {
                                indicator.textContent = 'Out of Stock';
                                indicator.className = 'text-sm font-bold text-red-600 mb-2 transition-all';
                                
                                if (topBadge) {
                                    topBadge.innerHTML = `<span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Out of Stock`;
                                    topBadge.className = 'text-xs font-bold uppercase tracking-widest border inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full transition-all bg-gray-100 text-gray-600 border-gray-300';
                                }
                                
                                if (outOfStockText) {
                                    outOfStockText.textContent = 'This product is currently out of stock.';
                                }
                                
                                if (cartWrapper) {
                                    cartWrapper.classList.remove('block');
                                    cartWrapper.classList.add('hidden');
                                }
                                if (outOfStockWrapper) {
                                    outOfStockWrapper.classList.remove('hidden');
                                    outOfStockWrapper.classList.add('block');
                                }
                            }
                            
                            indicator.style.opacity = '0.5';
                            setTimeout(() => indicator.style.opacity = '1', 300);
                        }
                    });
            } else if (echoRetries < 10) {
                echoRetries++;
                console.log('Echo not found, retrying... (' + echoRetries + '/10)');
                setTimeout(initStockEcho, 500);
            }
        }

        initStockEcho();
    });
</script>
@endpush
