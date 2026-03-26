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
            <x-productCard :product="$product" />
        @endforeach
    </div>

</div>
@endsection