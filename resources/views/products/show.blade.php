@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Product Details</h1>
        <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900 border py-2 px-4 rounded">Back to List</a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $product->name }}</h2>
        <p class="text-sm border inline-block px-2 rounded {{ $product->is_active ? 'bg-green-100 text-green-700 border-green-400' : 'bg-red-100 text-red-700 border-red-400' }} mb-4">
            {{ $product->is_active ? 'Active' : 'Inactive' }}
        </p>
        
        <p class="text-gray-700 mb-6 whitespace-pre-line">{{ $product->description }}</p>

        <div class="text-xl font-bold text-indigo-700 mb-6">
            Price: ₹{{ $product->price }}
        </div>

        @auth
        @if(auth()->user()->role === 'admin')
        <div class="flex space-x-4 border-t pt-4">
            <a href="{{ route('products.edit', $product->slug) }}" class="bg-yellow-500 text-white py-2 px-4 rounded hover:bg-yellow-600 transition">Edit Product</a>
            <form action="{{ route('products.destroy', $product->slug) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this product?');">
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
