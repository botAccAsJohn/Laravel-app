@props(['product'])

<div
    class="relative w-full max-w-sm border border-gray-200 bg-white rounded-[20px] shadow-sm hover:shadow-lg transition-shadow duration-300 overflow-hidden flex flex-col h-full group">

    <!-- Make entire card clickable (except z-20 elements) -->
    <a href="{{ $product->slug ? route('products.show', $product->slug) : '#' }}" class="absolute inset-0 z-10"
        aria-label="View {{ $product->name }}"></a>

    <!-- Top Image Section (Purple Gradient) -->
    <div
        class="relative w-full h-56 bg-gradient-to-br from-[#4c4270] via-[#7d72a6] to-[#b3a8e8] flex items-center justify-center p-4">

        <!-- Heart Icon -->
        <button
            class="absolute top-3 right-3 z-20 bg-white/20 hover:bg-white/40 transition rounded-full p-2 text-white/90 backdrop-blur-sm cursor-pointer active:scale-95">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z">
                </path>
            </svg>
        </button>

        @if($product->image_path)
            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}"
                class="max-h-full max-w-full object-contain filter drop-shadow-2xl group-hover:scale-105 transition-transform duration-500">
        @else
            <!-- Placeholder -->
            <svg class="w-16 h-16 text-white/50 filter drop-shadow hover:scale-105 transition-transform duration-500"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                </path>
            </svg>
        @endif
    </div>

    <!-- Bottom Content Section -->
    <div class="px-5 py-6 flex flex-col flex-grow">
        <h3 class="text-[1.15rem] font-bold text-gray-800 mb-2 leading-tight tracking-tight">{{ $product->name }}</h3>

        {{-- Category + status chips --}}
        <div class="flex flex-wrap gap-2 mb-3 z-20">
            @if($product->category)
                <span
                    class="border border-gray-300 rounded text-[9px] font-bold text-gray-500 px-1.5 py-0.5 tracking-wide uppercase">{{ $product->category->name }}</span>
            @endif
            <span
                class="border border-gray-300 rounded text-[9px] font-bold text-gray-500 px-1.5 py-0.5 tracking-wide uppercase">{{ $product->is_active ? 'Available' : 'Sold out' }}</span>

            @if($product->tags)
                @foreach($product->tags as $tag)
                    <span
                        class="border border-indigo-200 bg-indigo-50 text-indigo-600 rounded text-[9px] font-bold px-1.5 py-0.5 tracking-wide uppercase">{{ $tag }}</span>
                @endforeach
            @endif
        </div>

        <!-- Description -->
        <p class="text-[13px] text-gray-500 leading-relaxed line-clamp-3 mb-6 flex-grow">
            {{ $product->description }}
        </p>

        <!-- Footer: Price & Add to Cart -->
        <div class="flex items-end justify-between mt-auto">
            <div class="flex flex-col">
                <span class="text-[10px] font-bold text-gray-500 tracking-widest uppercase mb-0.5">Price</span>
                @if($product->discount_price)
                    <span class="text-xs line-through text-gray-400">@currency($product->price)</span>
                    <span
                        class="text-xl font-extrabold text-green-600 tracking-tight">@currency($product->discount_price)</span>
                @else
                    <span class="text-xl font-extrabold text-gray-900 tracking-tight">@currency($product->price)</span>
                @endif
            </div>

            <!-- Add to Cart Form (z-20 to be clickable over the absolute card link) -->
            <div class="relative z-20">
                <form action="{{ route('cart.add', $product->id) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="bg-[#5c5589] hover:bg-[#484271] border border-[#484271] text-white font-semibold text-sm px-5 py-2 rounded-lg shadow transition-colors active:scale-95 cursor-pointer">
                        Add to cart
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>