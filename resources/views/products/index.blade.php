@extends('layouts.app')

@push('scripts')
@vite('resources/js/products.js')
@endpush

@section('content')
<div class="bg-gray-50/50 min-h-screen">
    <div class="max-w-[1600px] mx-auto px-6 py-12 lg:py-16">

        {{-- Enhanced Header --}}
        <div class="mb-12 flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
            <div class="flex items-center gap-6">
                <div class="hidden sm:block h-16 w-2 rounded-full bg-indigo-600 shadow-[0_0_20px_rgba(79,70,229,0.4)]"></div>
                <div>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-2">
                        {{ $is_search ? __('products.search_findings') : __('products.title') }}
                    </h1>
                    <p class="text-slate-500 font-medium flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                        @if($is_search)
                        {{ trans_choice('common.products_found', $total_products, ['count' => number_format($total_products)]) }}
                        @else
                        {{ trans_choice('common.products_found', $all_products_count, ['count' => number_format($all_products_count)]) }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                @if(Auth::user()->role === 'admin')
                <a href="{{ route('products.create') }}"
                    class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-2xl bg-slate-900 px-8 py-4 text-sm font-bold text-white transition-all hover:bg-slate-800 hover:shadow-2xl hover:shadow-slate-200 active:scale-95">
                    <span class="relative z-10 transition-transform group-hover:-translate-x-1">{{ __('products.add_product') }}</span>
                    <svg class="relative z-10 w-4 h-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                </a>
                @endif
                @endauth
            </div>
        </div>

        @if(!$is_search)
        {{-- HOME VIEW: Horizontal Sections --}}
        <div class="space-y-20">

            {{-- Banner 1: Header Promo --}}
            <div class="relative overflow-hidden rounded-[3rem] bg-indigo-900 p-8 md:p-12 text-white shadow-2xl shadow-indigo-100">
                <div class="relative z-10 max-w-2xl space-y-4">
                    <span class="inline-block px-4 py-1.5 rounded-full bg-indigo-500/30 text-xs font-bold uppercase tracking-widest border border-indigo-400/30">{{ __('products.limited_edition') }}</span>
                    <h2 class="text-4xl md:text-6xl font-black leading-tight italic">{{ __('products.audio_promo_title') }}</h2>
                    <p class="text-indigo-100 text-lg font-medium">{{ __('products.audio_promo_desc') }}</p>
                    <a href="{{ route('products.index', ['sort' => 'popularity', 'categories[]' => 1]) }}" class="inline-flex items-center gap-2 text-indigo-400 font-black uppercase tracking-widest text-sm hover:text-white transition-colors group">
                        {{ __('products.explore_collection') }}
                        <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                        </svg>
                    </a>
                </div>
                <div class="absolute -right-20 -top-20 w-96 h-96 bg-indigo-500/20 rounded-full blur-[100px]"></div>
                <div class="absolute right-12 bottom-0 w-80 h-80 opacity-20 pointer-events-none hidden lg:block">
                    <svg class="w-full h-full" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-2-13.5l6.5 5.5-6.5 5.5V6.5z" />
                    </svg>
                </div>
            </div>

            <x-product-scroll-section :title="__('products.featured_selections')" :products="$featured" />

            {{-- Banner 2: Split View Promo --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <div class="group relative overflow-hidden rounded-[2.5rem] bg-orange-50 border border-orange-100 p-8 transition-all hover:shadow-xl hover:-translate-y-1">
                    <h3 class="text-2xl font-black text-orange-950 mb-2">{{ __('products.cookware_title') }}</h3>
                    <p class="text-orange-900/60 font-bold mb-6">{{ __('products.cookware_desc') }}</p>
                    <a href="{{ route('products.index', ['on_sale' => 1]) }}" class="text-sm font-black text-orange-600 uppercase tracking-widest hover:underline">{{ __('products.shop_now') }}</a>
                    <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-orange-200/40 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                </div>
                <div class="group relative overflow-hidden rounded-[2.5rem] bg-emerald-50 border border-emerald-100 p-8 transition-all hover:shadow-xl hover:-translate-y-1">
                    <h3 class="text-2xl font-black text-emerald-950 mb-2">{{ __('products.wfh_title') }}</h3>
                    <p class="text-emerald-900/60 font-bold mb-6">{{ __('products.wfh_desc') }}</p>
                    <a href="{{ route('products.index', ['categories[]' => 2]) }}" class="text-sm font-black text-emerald-600 uppercase tracking-widest hover:underline">{{ __('products.view_all') }}</a>
                    <div class="absolute -right-4 -bottom-4 w-32 h-32 bg-emerald-200/40 rounded-full blur-2xl group-hover:scale-150 transition-transform duration-700"></div>
                </div>
            </div>

            <x-product-scroll-section :title="__('products.new_arrivals')" :products="$new_arrivals" />

            <x-category-scroll :categories="$categories" />

            <x-product-scroll-section :title="__('products.on_sale_today')" :products="$on_sale" />

            <x-product-scroll-section :title="__('products.best_sellers')" :products="$best_sellers" />

            {{-- Call to Action / Footer Banner --}}
            <div class="bg-white border border-slate-200 rounded-[3rem] p-12 text-center space-y-6">
                <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ __('products.venture_deeper') }}</h2>
                <p class="text-slate-500 max-w-lg mx-auto font-medium">{{ __('products.venture_deeper_desc') }}</p>
                <div class="pt-4">
                    <a href="{{ route('products.index', ['sort' => 'newest', 'page' => 1]) }}"
                        class="inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-12 py-5 text-sm font-bold text-white shadow-xl shadow-indigo-100 transition-all hover:bg-indigo-700 hover:-translate-y-1 active:scale-95">
                        {{ __('products.browse_all') }}
                    </a>
                </div>
            </div>

        </div>
        @else
        {{-- SEARCH VIEW: Standard Grid with Filters --}}
        <div class="grid grid-cols-1 gap-12 lg:grid-cols-[340px_minmax(0,1fr)] xl:grid-cols-[380px_minmax(0,1fr)] items-start">
            {{-- Sticky Filter Sidebar --}}
            <x-product-filter-panel
                :categories="$categories"
                :filters="$filters"
                :price-range="$priceRange" />

            {{-- Products Stream --}}
            <div class="space-y-8">
                <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 animate-in fade-in slide-in-from-bottom-4 duration-700">
                    @forelse($products as $product)
                    <x-productCard :product="$product" />
                    @empty
                    <div class="col-span-full rounded-[40px] border-2 border-dashed border-slate-200 bg-slate-50/50 px-8 py-24 text-center">
                        <div class="inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-white shadow-xl text-slate-300 mb-6">
                            <svg class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                        <h2 class="text-2xl font-black text-slate-800 tracking-tight">{{ __('products.no_products') }}</h2>
                        <p class="mt-3 text-slate-500 font-medium max-w-sm mx-auto leading-relaxed">
                            {{ __('products.no_results_desc') }}
                        </p>
                        <a href="{{ route('products.index') }}"
                            class="mt-10 inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-10 py-4 text-sm font-bold text-white shadow-xl shadow-indigo-200 transition-all hover:bg-indigo-700 hover:-translate-y-1 active:scale-95">
                            {{ __('products.reset_params') }}
                        </a>
                    </div>
                    @endforelse
                </div>
                {{ $products->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection