@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    {{-- Breadcrumbs / Sub-header --}}
    <div class="border-b bg-white">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <nav class="flex text-sm font-medium text-slate-500">
                <a href="{{ route('products.index') }}" class="hover:text-amber-600 transition">Products</a>
                @if($product->category)
                    <span class="mx-2 text-slate-300">/</span>
                    <a href="{{ route('products.index', ['categories[]' => $product->category_id]) }}" class="hover:text-amber-600 transition">{{ $product->category->name }}</a>
                @endif
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-slate-900 truncate max-w-[200px] sm:max-w-md">{{ $product->name }}</span>
            </nav>
            
            <div class="flex items-center gap-4">
                @auth
                    @if(auth()->user()->role === 'admin')
                        <div class="flex items-center gap-2">
                            <a href="{{ route('products.edit', $product->slug) }}" class="text-xs font-bold uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition">Edit</a>
                            <span class="text-slate-200">|</span>
                            <form action="{{ route('products.destroy', $product->slug) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-bold uppercase tracking-widest text-red-500 hover:text-red-700 transition">Delete</button>
                            </form>
                        </div>
                    @endif
                @endauth
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-8 lg:py-12">
        {{-- Main Product Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 items-start">
            
            {{-- 1. Media Section --}}
            <div class="lg:col-span-4 xl:col-span-5">
                <div class="sticky top-24 space-y-4">
                    <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden aspect-square flex items-center justify-center p-8 group">
                        @if($product->image_path)
                            <img src="{{ asset('storage/' . $product->image_path) }}" 
                                 alt="{{ $product->name }}" 
                                 class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105"
                            >
                        @else
                            <div class="w-full h-full bg-slate-50 flex items-center justify-center text-slate-300">
                                <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif
                    </div>
                    <p class="text-center text-xs text-slate-400 font-medium">Hover to zoom in</p>
                </div>
            </div>

            {{-- 2. Product Details --}}
            <div class="lg:col-span-5 xl:col-span-4 space-y-8">
                <div>
                    @if($product->category)
                        <a href="#" class="text-sm font-bold text-amber-600 uppercase tracking-widest hover:underline decoration-2 underline-offset-4">
                            Visit the {{ $product->category->name }} Store
                        </a>
                    @endif
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 mt-2 leading-[1.15] tracking-tight">
                        {{ $product->name }}
                    </h1>
                </div>

                {{-- Pricing --}}
                <div class="border-y border-slate-100 py-6 space-y-4">
                    @if($product->discount_price)
                        <div class="flex items-baseline gap-4">
                            <span class="text-4xl font-light text-red-500">
                                -{{ round((1 - $product->discount_price / $product->price) * 100) }}%
                            </span>
                            <div class="flex flex-col">
                                <span class="text-3xl font-bold text-slate-900">
                                    @currency($product->discount_price)
                                </span>
                                <span class="text-sm text-slate-500">
                                    M.R.P.: <span class="line-through">@currency($product->price)</span>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-4xl font-bold text-slate-900">@currency($product->price)</div>
                    @endif
                    
                    <p class="text-sm text-slate-600 font-medium flex items-center gap-2">
                        Inclusive of all taxes
                        <span class="p-1 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 cursor-help">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        </span>
                    </p>
                </div>

                {{-- About section --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">About this item</h3>
                    <div class="prose prose-slate max-w-none text-slate-600 prose-sm leading-relaxed">
                        {!! nl2br(e($product->description)) !!}
                    </div>
                </div>

                {{-- Tags --}}
                @if($product->tags && count($product->tags))
                    <div class="pt-4 flex flex-wrap gap-2">
                        @foreach($product->tags as $tag)
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                #{{ $tag }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </div>

            {{-- 3. Purchase Sidebar (Buy Box) --}}
            <div class="lg:col-span-3">
                <div class="sticky top-24 bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 p-6 space-y-6">
                    <div>
                        <div class="text-3xl font-bold text-slate-900">
                            @currency($product->discount_price ?? $product->price)
                        </div>
                        <p class="text-sm text-slate-500 mt-1 font-medium">FREE delivery <strong>Tomorrow</strong>. Details</p>
                    </div>

                    {{-- Stock Status --}}
                    <div>
                        <div id="top-stock-badge" class="transition-all">
                            @if($product->quantity > 0)
                                <span class="text-xl font-bold text-green-600">In Stock</span>
                            @else
                                <span class="text-xl font-bold text-red-500">Currently unavailable</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500 mt-1">Ships from and sold by <strong>Prime Objects</strong>.</p>
                    </div>

                    {{-- Actions Wrapper --}}
                    <div id="product-action-section" 
                         data-product-id="{{ $product->id }}" 
                         data-is-active="{{ $product->is_active ? 'true' : 'false' }}"
                         class="space-y-4">
                        <div id="add-to-cart-wrapper" class="{{ $product->is_active && $product->quantity > 0 ? 'block' : 'hidden' }} space-y-4">
                            <form action="{{ route('cart.add', $product->id) }}" method="POST" class="space-y-4">
                                @csrf
                                {{-- Quantity Selector --}}
                                <div class="space-y-2">
                                    <label for="qty" class="text-xs font-bold uppercase tracking-widest text-slate-400">Select Quantity</label>
                                    <div class="flex items-center border border-slate-200 rounded-2xl overflow-hidden bg-slate-50 focus-within:ring-2 focus-within:ring-amber-500/20 transition-all">
                                        <button type="button" 
                                                onclick="const q = document.getElementById('qty'); q.value = Math.max(1, parseInt(q.value)-1);" 
                                                class="px-5 py-3 hover:bg-slate-100 transition text-slate-600 font-bold text-lg">-</button>
                                        <input type="number" name="quantity" id="qty" value="1" min="1" max="{{ $product->quantity }}"
                                               class="w-full text-center border-none bg-transparent focus:ring-0 font-bold text-slate-900" readonly>
                                        <button type="button" 
                                                onclick="const q = document.getElementById('qty'); const max = parseInt(q.max) || 999; if(parseInt(q.value) < max) q.value = parseInt(q.value)+1;" 
                                                class="px-5 py-3 hover:bg-slate-100 transition text-slate-600 font-bold text-lg">+</button>
                                    </div>
                                </div>
                                
                                <div class="space-y-3 pt-2">
                                    <button type="submit" 
                                            class="w-full bg-amber-400 hover:bg-amber-500 text-slate-900 font-bold py-4 rounded-2xl transition-all duration-300 shadow-md shadow-amber-200 active:scale-[0.98]">
                                        Add to Cart
                                    </button>
                                    <button type="button" 
                                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-2xl transition-all duration-300 shadow-md shadow-orange-200 active:scale-[0.98]">
                                        Buy Now
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Unavailable Message --}}
                        <div id="out-of-stock-wrapper" class="{{ !$product->is_active || $product->quantity <= 0 ? 'block' : 'hidden' }}">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                                <p id="unavailable-text" class="text-sm font-bold text-slate-600">
                                    {{ $product->is_active ? 'Currently out of stock.' : 'Currently unavailable.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Trust Badges --}}
                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-100">
                        <div class="flex flex-col items-center text-center space-y-1">
                            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Secure Transaction</span>
                        </div>
                        <div class="flex flex-col items-center text-center space-y-1">
                            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">Verified Seller</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script type="module">
    document.addEventListener('DOMContentLoaded', function () {
        const actionSection = document.getElementById('product-action-section');
        const productId = actionSection?.dataset.productId;
        const isActive = actionSection?.dataset.isActive === 'true';
        let echoRetries = 0;
        
        function initStockEcho() {
            if (window.Echo) {
                console.log('Echo initialized, subscribing to: product.' + productId);
                window.Echo.channel('product.' + productId)
                    .listen('.stock.changed', function (e) {
                        const newStock = e.new_stock;
                        const topBadge = document.getElementById('top-stock-badge');
                        const qtyInput = document.getElementById('qty');
                        const cartWrapper = document.getElementById('add-to-cart-wrapper');
                        const outOfStockWrapper = document.getElementById('out-of-stock-wrapper');
                        const outOfStockText = document.getElementById('unavailable-text');
                        
                        if (newStock > 0) {
                            if (topBadge) {
                                topBadge.innerHTML = `<span class="text-xl font-bold text-green-600">In Stock</span>`;
                            }
                            
                            if (qtyInput) {
                                qtyInput.max = newStock;
                                if (parseInt(qtyInput.value) > newStock) {
                                    qtyInput.value = newStock;
                                }
                            }
                            
                            if (isActive) {
                                if (cartWrapper) {
                                    cartWrapper.classList.remove('hidden');
                                    cartWrapper.classList.add('block');
                                }
                                if (outOfStockWrapper) {
                                    outOfStockWrapper.classList.remove('block');
                                    outOfStockWrapper.classList.add('hidden');
                                }
                            }
                        } else {
                            if (topBadge) {
                                topBadge.innerHTML = `<span class="text-xl font-bold text-red-500">Currently unavailable</span>`;
                            }
                            
                            if (outOfStockText) {
                                outOfStockText.textContent = 'Currently out of stock.';
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
                    });
            } else if (echoRetries < 10) {
                echoRetries++;
                setTimeout(initStockEcho, 500);
            }
        }

        initStockEcho();
    });
</script>
@endpush
