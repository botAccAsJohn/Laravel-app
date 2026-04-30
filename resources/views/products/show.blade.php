@extends('layouts.app')

@section('content')
<div class="bg-gray-50 min-h-screen">
    {{-- Breadcrumbs / Sub-header --}}
    <div class="border-b bg-white">
        <div class="max-w-7xl mx-auto px-4 py-3 flex items-center justify-between">
            <nav class="flex text-sm font-medium text-slate-500">
                <a href="{{ route('products.index') }}" class="hover:text-amber-600 transition">{{ __('common.products') }}</a>
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
                            <a href="{{ route('products.edit', $product->slug) }}" class="text-xs font-bold uppercase tracking-widest text-indigo-600 hover:text-indigo-800 transition">{{ __('products.edit_product') }}</a>
                            <span class="text-slate-200">|</span>
                            <form action="{{ route('products.destroy', $product->slug) }}" method="POST" onsubmit="return confirm('Delete this product?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-xs font-bold uppercase tracking-widest text-red-500 hover:text-red-700 transition">{{ __('products.delete_product') }}</button>
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
                        <img src="{{ $product->image_url }}" 
                             alt="{{ $product->name }}" 
                             class="w-full h-full object-contain transition-transform duration-500 group-hover:scale-105"
                        >
                    </div>
                    <p class="text-center text-xs text-slate-400 font-medium">{{ __('products.hover_zoom') }}</p>
                </div>
            </div>

            {{-- 2. Product Details --}}
            <div class="lg:col-span-5 xl:col-span-4 space-y-8">
                <div>
                    @if($product->category)
                        <a href="#" class="text-sm font-bold text-amber-600 uppercase tracking-widest hover:underline decoration-2 underline-offset-4">
                            {{ __('products.visit_store', ['name' => $product->category->name]) }}
                        </a>
                    @endif
                    <h1 class="text-3xl md:text-4xl font-black text-slate-900 mt-2 leading-[1.15] tracking-tight">
                        {{ $product->name }}
                    </h1>
                    @if($product->review_count > 0)
                        <div class="flex items-center gap-2 mt-3">
                            <div class="flex text-amber-400">
                                @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-5 h-5 {{ $i <= round($product->average_rating) ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                    </svg>
                                @endfor
                            </div>
                            <span class="text-sm font-bold text-slate-700">{{ $product->average_rating }}</span>
                            <span class="text-sm text-slate-500">{{ __('products.ratings_count', ['count' => $product->review_count]) }}</span>
                        </div>
                    @endif
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
                                    {{ __('products.mrp') }}: <span class="line-through">@currency($product->price)</span>
                                </span>
                            </div>
                        </div>
                    @else
                        <div class="text-4xl font-bold text-slate-900">@currency($product->price)</div>
                    @endif
                    
                    <p class="text-sm text-slate-600 font-medium flex items-center gap-2">
                        {{ __('products.inclusive_taxes') }}
                        <span class="p-1 rounded-full bg-slate-100 text-slate-400 hover:text-slate-600 cursor-help">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                        </span>
                    </p>
                </div>

                {{-- About section --}}
                <div class="space-y-4">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">{{ __('products.about_item') }}</h3>
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

                {{-- Customer Reviews --}}
                <div class="mt-12 space-y-6 pt-8 border-t border-slate-200">
                    <h3 class="text-xl font-bold text-slate-900">{{ __('products.customer_reviews') }}</h3>
                    
                    @auth
                        <div class="bg-slate-50 rounded-2xl p-6 border border-slate-100">
                            <h4 class="text-sm font-bold text-slate-900 mb-4">{{ __('products.write_review') }}</h4>
                            <form action="{{ route('reviews.store', $product) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('products.rating') }}</label>
                                    <select name="rating" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all font-medium text-slate-700" required>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Good</option>
                                        <option value="3">3 - Average</option>
                                        <option value="2">2 - Poor</option>
                                        <option value="1">1 - Terrible</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold uppercase tracking-widest text-slate-500 mb-2">{{ __('products.review_optional') }}</label>
                                    <textarea name="review_text" rows="3" class="w-full bg-white border border-slate-200 rounded-xl px-4 py-3 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-all text-slate-700" placeholder="{{ __('products.review_placeholder') }}"></textarea>
                                </div>
                                <button type="submit" class="bg-slate-900 hover:bg-indigo-600 text-white font-bold py-3 px-6 rounded-xl transition-colors duration-300 border-0 cursor-pointer">
                                    {{ __('products.submit_review') }}
                                </button>
                            </form>
                        </div>
                    @endauth
                    
                    <div class="space-y-4">
                        @forelse($product->reviews as $review)
                            <div class="border-b border-slate-100 pb-4">
                                <div class="flex items-center gap-2 mb-1">
                                    <div class="flex text-amber-400">
                                        @for($i = 1; $i <= 5; $i++)
                                            <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-slate-200' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                        @endfor
                                    </div>
                                    <span class="text-xs font-bold text-slate-700">{{ $review->user->name ?? 'Guest' }}</span>
                                    <span class="text-[10px] text-slate-400">{{ $review->created_at->isoFormat('LL') }}</span>
                                </div>
                                @if($review->review_text)
                                    <p class="text-sm text-slate-600 mt-2">{{ $review->review_text }}</p>
                                @endif
                            </div>
                        @empty
                            <p class="text-sm text-slate-500 italic">{{ __('products.no_reviews') }}</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- 3. Purchase Sidebar (Buy Box) --}}
            <div class="lg:col-span-3">
                <div class="sticky top-24 bg-white rounded-[2rem] border border-slate-200 shadow-xl shadow-slate-200/50 p-6 space-y-6">
                    <div>
                        <div class="text-3xl font-bold text-slate-900">
                            @currency($product->discount_price ?? $product->price)
                        </div>
                        <p class="text-sm text-slate-500 mt-1 font-medium">{{ __('products.free_delivery') }}. Details</p>
                    </div>

                    {{-- Stock Status --}}
                    <div>
                        <div id="top-stock-badge" class="transition-all">
                            @if($product->is_active && $product->quantity > 0)
                                <span class="text-xl font-bold text-green-600">{{ __('In Stock') }}</span>
                            @else
                                <span class="text-xl font-bold text-red-500">{{ $product->is_active ? __('Out of Stock') : __('Unavailable') }}</span>
                            @endif
                        </div>
                        <p class="text-sm text-slate-500 mt-1">{{ __('products.ships_from') }}.</p>
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
                                    <label for="qty" class="text-xs font-bold uppercase tracking-widest text-slate-400">{{ __('Quantity') }}</label>
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
                                        {{ __('Add to Cart') }}
                                    </button>
                                    <button type="button" 
                                            class="w-full bg-orange-500 hover:bg-orange-600 text-white font-bold py-4 rounded-2xl transition-all duration-300 shadow-md shadow-orange-200 active:scale-[0.98]">
                                        {{ __('Buy Now') }}
                                    </button>
                                </div>
                            </form>
                        </div>

                        {{-- Unavailable Message --}}
                        <div id="out-of-stock-wrapper" class="{{ !$product->is_active || $product->quantity <= 0 ? 'block' : 'hidden' }}">
                            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 text-center">
                                <p id="unavailable-text" class="text-sm font-bold text-slate-600">
                                    {{ $product->is_active ? __('products.out_of_stock') : __('products.unavailable') }}
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
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">{{ __('products.secure_transaction') }}</span>
                        </div>
                        <div class="flex flex-col items-center text-center space-y-1">
                            <div class="w-10 h-10 rounded-full bg-slate-50 flex items-center justify-center text-slate-400">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            </div>
                            <span class="text-[10px] font-bold text-slate-500 uppercase tracking-tighter">{{ __('products.verified_seller') }}</span>
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
