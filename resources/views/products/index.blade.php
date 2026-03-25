@extends('layouts.app')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            Products
        </h1>

        @auth
        @if(auth()->user()->role === 'admin')
        <a href="{{ route('products.create') }}"
            class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
            Create New Product
        </a>
        @endif
        @endauth
    </div>

    <!-- Success Message -->
    @if(session('success'))
    <div class="mb-6 p-4 border border-green-400 bg-green-100 text-green-700 rounded-lg">
        {{ session('success') }}
    </div>
    @endif

    <!-- Products List -->
    <div class="flex flex-wrap gap-5">
        @foreach($products as $product)

        <!-- Product Horizontal Card -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 flex flex-col sm:flex-row justify-between items-center p-5 gap-4">

            <!-- Content (Left side) -->
            <div class="flex-1 text-center sm:text-left flex flex-col sm:flex-row items-center sm:items-start gap-4">
                @if($product->image_path)
                    <img src="{{ asset('storage/' . $product->image_path) }}" alt="{{ $product->name }}" class="w-24 h-24 object-cover rounded-lg border border-gray-200">
                @else
                    <div class="w-24 h-24 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 border border-gray-200">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                    </div>
                @endif
                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-1">
                        {{ $product->name }}
                    </h2>

                    <p class="text-lg font-bold text-indigo-600 mb-1">
                        ₹{{ $product->price }}
                    </p>
                    
                    @if($product->category)
                    <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">{{ $product->category }}</span>
                    @endif
                </div>
            </div>

            <!-- Actions (Right side) -->
            <div class="flex flex-wrap justify-center sm:justify-end gap-2 w-full sm:w-auto">

                <!-- View -->
                <a href="{{ route('products.show', $product->slug) }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm font-medium text-center">
                    View
                </a>

                @auth
                @if(auth()->user()->role === 'admin')

                <!-- Edit -->
                <a href="{{ route('products.edit', $product->slug) }}"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition text-sm font-medium text-center">
                    Edit
                </a>

                <!-- Delete -->
                <form action="{{ route('products.destroy', $product->slug) }}"
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this product?');"
                    class="inline">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm font-medium">
                        Delete
                    </button>
                </form>

                @endif
                @endauth

            </div>

        </div>

        @endforeach
    </div>

</div>
@endsection