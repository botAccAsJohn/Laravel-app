@extends('layouts.app')

@push('scripts')
@vite('resources/js/products.js')
@endpush

@section('content')
<div class="max-w-[1600px] mx-auto px-6 py-12 lg:py-16">

    {{-- Enhanced Header --}}
    <div class="mb-12 flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
        <div class="flex items-center gap-6">
            <div class="hidden sm:block h-16 w-2 rounded-full bg-indigo-600 shadow-[0_0_20px_rgba(79,70,229,0.4)]"></div>
            <div>
                <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-2">Prime Objects</h1>
                <p class="text-slate-500 font-medium flex items-center gap-2">
                    <span class="inline-flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                    Curated collection of <span class="text-slate-900 font-bold underline decoration-indigo-500/30 decoration-4 underline-offset-4">{{ $all_products_count }}</span> items
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @auth
            @if(Auth::user()->role === 'admin')
            <a href="{{ route('products.create') }}"
                class="group relative inline-flex items-center justify-center gap-2 overflow-hidden rounded-2xl bg-slate-900 px-8 py-4 text-sm font-bold text-white transition-all hover:bg-slate-800 hover:shadow-2xl hover:shadow-slate-200 active:scale-95">
                <span class="relative z-10">Initialize New Product</span>
                <svg class="relative z-10 w-4 h-4 transition-transform group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                </svg>
            </a>
            @endif
            @endauth
        </div>
    </div>

    {{-- Main Layout Grid --}}
    <div class="grid grid-cols-1 gap-12 lg:grid-cols-[340px_minmax(0,1fr)] xl:grid-cols-[380px_minmax(0,1fr)] items-start">

        {{-- Sticky Filter Sidebar --}}
        <x-product-filter-panel
            :categories="$categories"
            :filters="$filters"
            :price-range="$priceRange" />

        {{-- Products Stream --}}
        <div class="space-y-8">
            {{-- Results Metadata --}}
            <div class="flex items-center justify-between px-2">
                <div class="text-[13px] font-bold uppercase tracking-widest text-slate-400">
                    Search Results — <span class="text-indigo-600">{{ $total_products }}</span> Findings
                </div>
            </div>

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
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">Zero Matches Found</h2>
                    <p class="mt-3 text-slate-500 font-medium max-w-sm mx-auto leading-relaxed">
                        We couldn't find any products matching your current parameters. Try adjusting your filters or sweeping the reset.
                    </p>
                    <a href="{{ route('products.index') }}"
                        class="mt-10 inline-flex items-center justify-center rounded-2xl bg-indigo-600 px-10 py-4 text-sm font-bold text-white shadow-xl shadow-indigo-200 transition-all hover:bg-indigo-700 hover:-translate-y-1 active:scale-95">
                        Reset Search Parameters
                    </a>
                </div>
                @endforelse
            </div>
            {{ $products->withQueryString()->links() }}
        </div>
    </div>

</div>
@endsection