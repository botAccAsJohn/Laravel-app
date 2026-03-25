@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Product Details</h1>
        <a href="{{ route('products.index') }}" class="text-gray-600 hover:text-gray-900 border py-2 px-4 rounded">Back to List</a>
    </div>

    <div class="bg-white shadow-md rounded-lg p-6">
        @if($product->image_path)
            <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="w-full h-64 object-cover rounded-lg mb-6 border border-gray-200">
        @else
            <div class="w-full h-64 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 mb-6 border border-gray-200">
                <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
            </div>
        @endif
        
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
