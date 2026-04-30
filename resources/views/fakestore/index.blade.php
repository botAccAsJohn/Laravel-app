@extends('layouts.app')

@section('title', 'External API Products')

@section('content')
<div class="bg-gray-50/50 min-h-screen py-12 lg:py-16">
    <div class="max-w-[1600px] mx-auto px-6">

        {{-- Header --}}
        <div class="mb-12 flex flex-col gap-8 md:flex-row md:items-end md:justify-between">
            <div class="flex items-center gap-6">
                <div class="hidden sm:block h-16 w-2 rounded-full bg-blue-600 shadow-[0_0_20px_rgba(37,99,235,0.4)]"></div>
                <div>
                    <h1 class="text-4xl md:text-5xl font-black text-slate-900 tracking-tight mb-2">
                        FakeStore API Selection
                    </h1>
                    <p class="text-slate-500 font-medium flex items-center gap-2">
                        <span class="inline-flex h-2 w-2 rounded-full bg-green-500 animate-pulse"></span>
                        Live data from external source
                    </p>
                </div>
            </div>
        </div>

        {{-- Products Grid --}}
        <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 animate-in fade-in slide-in-from-bottom-4 duration-700">
            @forelse($products as $product)
                <div class="relative w-full border border-slate-200/60 bg-white rounded-[32px] shadow-[0_8px_30px_rgb(0,0,0,0.04)] hover:shadow-[0_32px_64px_-16px_rgba(0,0,0,0.12)] transition-all duration-500 flex flex-col h-full group isolate">
                    
                    {{-- Top Image Section --}}
                    <div class="relative w-full h-64 overflow-hidden bg-[#f8fafc] flex items-center justify-center p-8 rounded-t-[32px]">
                        {{-- Soft Gradient Overlay --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-blue-500/5 via-purple-500/5 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500"></div>
                        
                        {{-- Product Image --}}
                        <img src="{{ $product['image'] }}" alt="{{ $product['title'] }}"
                            class="max-h-full max-w-full object-contain filter drop-shadow-[0_20px_50px_rgba(0,0,0,0.1)] group-hover:scale-110 group-hover:-rotate-2 transition-transform duration-700 ease-out">
                    </div>

                    {{-- Content Section --}}
                    <div class="px-7 py-8 flex flex-col flex-grow">
                        <div class="flex flex-col gap-1 mb-3">
                            <h3 class="text-lg font-extrabold text-slate-900 leading-tight tracking-tight group-hover:text-blue-600 transition-colors duration-300 line-clamp-2" title="{{ $product['title'] }}">
                                {{ $product['title'] }}
                            </h3>
                            
                            {{-- Rating --}}
                            @if(isset($product['rating']))
                                <div class="flex items-center gap-1 mt-1">
                                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                    <span class="text-xs font-bold text-slate-700">{{ $product['rating']['rate'] }}</span>
                                    <span class="text-xs text-slate-400">({{ $product['rating']['count'] }} reviews)</span>
                                </div>
                            @endif
                        </div>

                        {{-- Metadata Badges --}}
                        <div class="flex flex-wrap gap-1.5 mb-5 z-20">
                            <span class="bg-blue-50 text-blue-600 border border-blue-100/50 rounded-lg text-[9px] font-bold px-2.5 py-1 tracking-wider uppercase">
                                {{ $product['category'] }}
                            </span>
                        </div>

                        {{-- Description --}}
                        <p class="text-[13px] text-slate-500 leading-relaxed line-clamp-3 mb-8 flex-grow" title="{{ $product['description'] }}">
                            {{ Str::limit($product['description'], 120) }}
                        </p>

                        {{-- Footer: Price --}}
                        <div class="flex items-center justify-between mt-auto pt-6 border-t border-slate-50">
                            <div class="flex flex-col">
                                <span class="text-[11px] text-slate-400 font-bold uppercase tracking-widest mb-1.5">Price</span>
                                <span class="text-2xl font-black text-slate-900 tracking-tighter">{{ format_price($product['price']) }}</span>
                            </div>
                            
                            {{-- Fake Action Button --}}
                            <button class="h-10 px-4 flex items-center justify-center bg-slate-900 hover:bg-blue-600 text-white font-bold rounded-xl shadow-lg transition-all duration-300 active:scale-95 cursor-pointer">
                                View Item
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-20 text-center">
                    <p class="text-slate-500 font-medium text-lg">No products found from the API.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
