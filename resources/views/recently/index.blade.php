@extends('layouts.app')

@section('title', $page_title ?? 'Recently Viewed')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Recently Viewed</h1>
            <p class="text-gray-500">Your last 10 viewed products</p>
        </div>

        @if($products->isNotEmpty())
        <form action="{{ route('recently.clear') }}" method="POST" onsubmit="return confirm('Clear your browsing history?')">
            @csrf
            <button type="submit" class="text-red-600 hover:text-red-700 font-medium flex items-center gap-2 px-4 py-2 rounded-lg border border-red-100 hover:bg-red-50 transition shadow-sm">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                Clear History
            </button>
        </form>
        @endif
    </div>

    @if($products->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-12 text-center">
            <div class="w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-6">
                <svg class="h-10 w-10 text-gray-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h2 class="text-xl font-semibold text-gray-800 mb-2">No history yet</h2>
            <p class="text-gray-500 mb-8 max-w-md mx-auto">Products you view while browsing will appear here so you can easily find them again.</p>
            <a href="{{ route('products.index') }}" class="inline-flex items-center justify-center bg-indigo-600 text-white px-8 py-3 rounded-xl font-bold hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                Start Shopping
            </a>
        </div>
    @else
        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach($products as $product)
                <x-productCard :product="$product" />
            @endforeach
        </div>
    @endif

</div>
@endsection