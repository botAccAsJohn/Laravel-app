@props([
    'categories' => collect(),
    'filters' => [],
    'priceRange' => ['min' => 0, 'max' => 0],
])

@php
    $sortOptions = [
        'newest' => 'Newest First',
        'popularity' => 'Popularity (most sales)',
        'price_low_high' => 'Price: Low to High',
        'price_high_low' => 'Price: High to Low',
    ];

    $activeCount = collect([
        $filters['categories'] ?? [],
        $filters['min_price'] ?? null,
        $filters['max_price'] ?? null,
        $filters['in_stock'] ?? false,
        $filters['on_sale'] ?? false,
        ($filters['sort'] ?? 'newest') !== 'newest' ? $filters['sort'] : null,
    ])->filter(fn($value) => is_array($value) ? !empty($value) : filled($value))->count();
@endphp

<aside class="w-full lg:sticky lg:top-8 isolate">
    <div class="overflow-hidden rounded-[24px] border border-slate-200 bg-white shadow-[0_8px_30px_rgb(0,0,0,0.04)] transition-all duration-300">
        {{-- Header Section --}}
        <div class="px-8 py-6 border-b border-slate-100 bg-slate-50/50">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold text-slate-900 tracking-tight">Filter Choice</h2>
                    <p class="text-[11px] font-medium text-slate-500 uppercase tracking-widest mt-1">Refine your view</p>
                </div>

                @if($activeCount)
                    <div class="flex h-7 px-3 items-center justify-center rounded-full bg-indigo-600 text-[11px] font-bold text-white shadow-md shadow-indigo-200 animate-in zoom-in duration-300">
                        {{ $activeCount }} Selected
                    </div>
                @endif
            </div>
        </div>

        <form method="GET" action="{{ route('products.index') }}" class="space-y-8 px-8 py-8">
            {{-- Category Filter (Checkboxes) --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-slate-800">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    <label class="text-[12px] font-bold uppercase tracking-widest text-slate-500">Multiple Categories</label>
                </div>
                
                <div class="grid grid-cols-1 gap-3 max-h-48 overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($categories as $category)
                        <label class="relative flex items-center group cursor-pointer">
                            <input type="checkbox" name="categories[]" value="{{ $category->id }}"
                                @checked(in_array($category->id, $filters['categories'] ?? []))
                                class="peer h-5 w-5 rounded-md border-slate-200 text-indigo-600 focus:ring-indigo-500/20 transition-all cursor-pointer">
                            <span class="ml-3 text-sm font-semibold text-slate-600 group-hover:text-indigo-600 transition-colors peer-checked:text-indigo-600">
                                {{ $category->name }}
                            </span>
                        </label>
                    @endforeach
                </div>
            </div>

            {{-- Status Filters --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-slate-800">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <label class="text-[12px] font-bold uppercase tracking-widest text-slate-500">Status</label>
                </div>
                
                <div class="flex flex-col gap-3">
                    <label class="relative flex items-center group cursor-pointer">
                        <input type="checkbox" name="in_stock" value="1"
                            @checked($filters['in_stock'] ?? false)
                            class="peer h-5 w-5 rounded-md border-slate-200 text-indigo-600 focus:ring-indigo-500/20 transition-all cursor-pointer">
                        <span class="ml-3 text-sm font-semibold text-slate-600 group-hover:text-indigo-600 transition-colors peer-checked:text-indigo-600">
                            In Stock Only
                        </span>
                    </label>

                    <label class="relative flex items-center group cursor-pointer">
                        <input type="checkbox" name="on_sale" value="1"
                            @checked($filters['on_sale'] ?? false)
                            class="peer h-5 w-5 rounded-md border-slate-200 text-indigo-600 focus:ring-indigo-500/20 transition-all cursor-pointer">
                        <span class="ml-3 text-sm font-semibold text-slate-600 group-hover:text-indigo-600 transition-colors peer-checked:text-indigo-600">
                            On Sale (Discount)
                        </span>
                    </label>
                </div>
            </div>

            {{-- Price Range Filter --}}
            <div class="space-y-4">
                <div class="flex items-center gap-2 text-slate-800">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <label class="text-[12px] font-bold uppercase tracking-widest text-slate-500">Price Range</label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 uppercase">Min</span>
                        <input
                            id="min_price"
                            name="min_price"
                            type="number"
                            min="0"
                            step="0.01"
                            value="{{ $filters['min_price'] ?? '' }}"
                            placeholder="{{ number_format($priceRange['min'], 2, '.', '') }}"
                            class="w-full rounded-xl border border-slate-200 bg-white pl-12 pr-4 py-3 text-sm font-bold text-slate-700 outline-none transition-all hover:border-indigo-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5"
                        >
                    </div>

                    <div class="relative">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400 uppercase">Max</span>
                        <input
                            id="max_price"
                            name="max_price"
                            type="number"
                            min="0"
                            step="0.01"
                            value="{{ $filters['max_price'] ?? '' }}"
                            placeholder="{{ number_format($priceRange['max'], 2, '.', '') }}"
                            class="w-full rounded-xl border border-slate-200 bg-white pl-12 pr-4 py-3 text-sm font-bold text-slate-700 outline-none transition-all hover:border-indigo-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5"
                        >
                    </div>
                </div>
            </div>

            {{-- Sort Options --}}
            <div class="space-y-3">
                <div class="flex items-center gap-2 text-slate-800">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12" />
                    </svg>
                    <label for="sort" class="text-[12px] font-bold uppercase tracking-widest text-slate-500">Sorting Strategy</label>
                </div>
                <div class="relative">
                    <select
                        id="sort"
                        name="sort"
                        class="w-full appearance-none rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700 outline-none transition-all hover:border-indigo-300 focus:border-indigo-500 focus:ring-4 focus:ring-indigo-500/5"
                    >
                        @foreach($sortOptions as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['sort'] ?? 'newest') === $value)>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute right-4 top-1/2 -translate-y-1/2 text-slate-300">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m19 9-7 7-7-7" /></svg>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-col gap-3 pt-4">
                <button
                    type="submit"
                    class="h-12 w-full flex items-center justify-center rounded-xl bg-slate-900 border-2 border-slate-900 text-sm font-bold text-white shadow-lg shadow-slate-200 transition-all hover:bg-indigo-600 hover:border-indigo-600 active:scale-95"
                >
                    Update Results
                </button>

                <a
                    href="{{ route('products.index') }}"
                    class="h-12 w-full flex items-center justify-center rounded-xl border-2 border-slate-100 bg-white text-sm font-bold text-slate-500 transition-all hover:border-rose-100 hover:bg-rose-50 hover:text-rose-500 active:scale-95"
                >
                    Reset Parameters
                </a>
            </div>
        </form>
    </div>
</aside>



