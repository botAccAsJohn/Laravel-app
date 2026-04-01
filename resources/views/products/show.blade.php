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
        <span class="text-sm border inline-block px-2 rounded mb-4
            {{ $product->is_active ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400' }}">
            {{ $product->is_active ? 'Active' : 'Inactive' }}
        </span>

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
