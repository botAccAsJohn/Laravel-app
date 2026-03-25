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
            <div class="flex-1 text-center sm:text-left">
                <h2 class="text-xl font-semibold text-gray-800 mb-1">
                    {{ $product->name }}
                </h2>

                <p class="text-lg font-bold text-indigo-600">
                    ₹{{ $product->price }}
                </p>
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