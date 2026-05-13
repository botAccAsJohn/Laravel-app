@props(['product'])

<div
    class="relative w-full border border-slate-200/60 bg-white rounded-[32px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_32px_64px_-16px_rgba(0,0,0,0.12)] transition-all duration-500 flex flex-col h-full group isolate">

    {{-- Make entire card clickable (except z-20 elements) --}}
    <a href="{{ $product->slug ? route('products.show', $product->slug) : '#' }}" class="absolute inset-0 z-10"
        aria-label="{{ __('common.view') }} {{ $product->name }}"></a>

    {{-- Top Image Section --}}
    <div
        class="relative w-full h-64 overflow-hidden bg-[#f8fafc] flex items-center justify-center p-8 rounded-t-[32px]">
        {{-- Soft Gradient Overlay --}}
        <div class="absolute inset-0 bg-gradient-to-br from-indigo-500/5 via-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>

        {{-- Wishlist Toggle --}}
        <button
            class="absolute top-5 right-5 z-20 h-10 w-10 bg-white/80 hover:bg-white transition-all rounded-full flex items-center justify-center text-slate-400 hover:text-rose-500 shadow-sm backdrop-blur-md cursor-pointer active:scale-90">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                </path>
            </svg>
        </button>

        {{-- Product Image --}}
        <img src="{{ $product->image_url }}" alt="{{ $product->name }}"
            class="max-h-full max-w-full object-contain filter drop-shadow-[0_20px_50px_rgba(0,0,0,0.1)] group-hover:scale-110 group-hover:-rotate-2 transition-transform duration-700 ease-out">

        {{-- Stock Badge --}}
        @if(!$product->is_active || ($product->quantity ?? 1) <= 0)
            <div class="absolute inset-0 bg-white/60 backdrop-blur-[2px] z-15 flex items-center justify-center">
            <span class="bg-slate-900 text-white text-[10px] font-bold px-4 py-2 rounded-full uppercase tracking-widest shadow-xl">{{ __('products.out_of_stock') }}</span>
    </div>
    @endif
</div>

{{-- Content Section --}}
<div class="px-7 py-8 flex flex-col flex-grow">
    <div class="flex flex-col gap-1 mb-3">
        <h3 class="text-lg font-extrabold text-slate-900 leading-tight tracking-tight group-hover:text-indigo-600 transition-colors duration-300 line-clamp-2">
            {{ $product->name }}
        </h3>
        @if($product->review_count > 0)
        <div class="flex items-center gap-1">
            <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
            </svg>
            <span class="text-xs font-bold text-slate-700">{{ $product->average_rating }}</span>
            <span class="text-xs text-slate-400">({{ $product->review_count }})</span>
        </div>
        @endif
    </div>

    {{-- Metadata Badges --}}
    <div class="flex flex-wrap gap-1.5 mb-5 z-20">
        @if($product->category)
        <span class="bg-indigo-50 text-indigo-600 border border-indigo-100/50 rounded-lg text-[9px] font-bold px-2.5 py-1 tracking-wider uppercase">
            {{ $product->category->name }}
        </span>
        @endif

        @if($product->tags)
        @foreach(array_slice($product->tags, 0, 2) as $tag)
        <span class="bg-slate-50 text-slate-500 border border-slate-100 rounded-lg text-[9px] font-bold px-2.5 py-1 tracking-wider uppercase">
            {{ $tag }}
        </span>
        @endforeach
        @endif
    </div>

    {{-- Description --}}
    <p class="text-[13px] text-slate-500 leading-relaxed line-clamp-2 mb-8 flex-grow">
        {{ \Illuminate\Support\Str::limit($product->description, 100) }}
    </p>

    {{-- Footer: Price & Add to Cart --}}
    <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-50">
        <div class="flex flex-col">
            @if($product->discount_price)
            <span class="text-[11px] line-through text-slate-400 font-medium mb-0.5">@currency($product->price)</span>
            <span class="text-2xl font-black text-indigo-600 tracking-tighter">@currency($product->discount_price)</span>
            @else
            <span class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1.5">{{ __('products.market_price') }}</span>
            <span class="text-2xl font-black text-slate-900 tracking-tighter">@currency($product->price)</span>
            @endif
        </div>

        <div class="relative z-20">
            <livewire:add-to-cart :product="$product" :key="'add-to-cart-'.$product->id" />
        </div>
    </div>
</div>
</div>


<!-- <form action="{{ route('cart.add', $product->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="h-12 w-12 flex items-center justify-center bg-slate-900 hover:bg-indigo-600 text-white rounded-2xl shadow-lg transition-all duration-300 hover:rotate-6 active:scale-90 cursor-pointer group/btn"
                        title="Add to Cart">
                        <svg class="w-5 h-5 transition-transform group-hover/btn:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                </form> -->