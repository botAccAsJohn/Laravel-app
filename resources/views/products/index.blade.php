@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">
            Products
        </h1>

        <p>
            Total products: 
            <strong>{{ $total_products ?? count($products) }}</strong>
        </p>

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

    <!-- Products Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">

        @foreach($products as $product)

        <!-- Product Card -->
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-4 flex flex-col">

            <!-- Image -->
            @if($product->image_path)
            <img src="{{ asset('storage/' . $product->image_path) }}"
                alt="{{ $product->name }}"
                class="w-full h-40 object-cover rounded-lg mb-3">
            @else
            <div class="w-full h-40 bg-gray-100 rounded-lg flex items-center justify-center text-gray-400 mb-3 border">
                No Image
            </div>
            @endif

            <!-- Content -->
            <div class="text-center flex-grow">
                <h2 class="text-lg font-semibold text-gray-800 mb-1">
                    {{ $product->name }}
                </h2>

                <p class="text-indigo-600 font-bold mb-2">
                    ₹{{ $product->price }}
                </p>

                @if($product->category)
                <span class="inline-block bg-gray-100 text-gray-600 text-xs px-2 py-1 rounded-full">
                    {{ $product->category }}
                </span>
                @endif
            </div>

            <!-- Actions -->
            <div class="mt-4 flex flex-col gap-2">

                <!-- View -->
                <a href="{{ route('products.show', $product->slug) }}"
                    class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition text-sm text-center">
                    View
                </a>

                @auth
                @if(auth()->user()->role === 'admin')

                <!-- Edit -->
                <a href="{{ route('products.edit', $product->slug) }}"
                    class="bg-yellow-400 text-gray-900 px-4 py-2 rounded-lg hover:bg-yellow-500 transition text-sm text-center">
                    Edit
                </a>

                <!-- Delete -->
                <form action="{{ route('products.destroy', $product->slug) }}"
                    method="POST"
                    onsubmit="return confirm('Are you sure you want to delete this product?');">
                    @csrf
                    @method('DELETE')

                    <button type="submit"
                        class="w-full bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition text-sm">
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