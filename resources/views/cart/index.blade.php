@extends('layouts.app')

@section('title', 'Shopping Cart')

@section('content')
<div class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-7xl mx-auto px-4 flex flex-col lg:flex-row gap-6">

        {{-- LEFT COLUMN: CART ITEMS --}}
        <div class="w-full {{ empty($cart) ? 'max-w-4xl mx-auto py-10' : 'lg:w-3/4' }} flex flex-col gap-4">
            <div class="bg-white p-6 shadow-sm">
                <div class="flex justify-between items-end border-b pb-2 mb-4">
                    <h1 class="text-3xl font-medium text-gray-900">Shopping Cart</h1>
                    <span class="text-gray-500 text-sm hidden sm:block">Price</span>
                </div>

                @empty($cart)
                    <div class="py-16 mt-12 text-center text-gray-600 flex flex-col items-center">
                        <div class="mb-6 p-4 bg-gray-50 rounded-full border border-gray-100 shadow-inner text-gray-300">
                            <svg class="h-20 w-20 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 11V8m0 0L10 10m2-2l2 2" />
                            </svg>
                        </div>
                        <p class="text-2xl font-semibold text-gray-900 mb-2">Your Shopping Cart is empty.</p>
                        <p class="text-gray-500 mb-8 max-w-xs px-4 mx-auto">Your cart is feeling a bit light! Let's find some amazing products for you.</p>
                        <a href="{{ route('products.index') }}" class="inline-flex items-center px-8 py-3 bg-blue-600 text-white font-bold rounded-full hover:bg-blue-700 shadow-md transition-all duration-300 hover:-translate-y-1">
                            Explore Products
                        </a>
                    </div>
                @else
                    @foreach ($cart as $item)
                        @php
                            $model = $cartModels[$item['id']] ?? null;
                        @endphp
                        <div class="flex flex-col sm:flex-row py-4 border-b gap-4">
                            
                            {{-- Product Image --}}
                            <div class="sm:w-1/4 md:w-1/5 flex-shrink-0">
                                @if($model && $model->image_path)
                                    <img src="{{ asset('storage/' . $model->image_path) }}" alt="{{ $item['name'] }}" class="w-full h-auto object-contain max-h-48">
                                @else
                                    <div class="w-full h-32 bg-gray-200 flex items-center justify-center text-gray-500 rounded">No Image</div>
                                @endif
                            </div>

                            {{-- Product Details --}}
                            <div class="sm:w-full md:w-3/5 flex flex-col">
                                <a href="{{ $model ? route('products.show', $model->slug) : '#' }}" class="text-lg font-medium text-blue-900 hover:underline mb-1">
                                    {{ $item['name'] }}
                                </a>
                                <p class="text-sm text-green-700 mb-1">In stock</p>
                                <p class="text-xs text-gray-500 mb-2">Eligible for FREE Shipping</p>
                                
                                <div class="flex items-center text-xs text-gray-700 mb-3">
                                    <input type="checkbox" class="mr-1 h-3 w-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>This will be a gift</span>
                                </div>

                                {{-- Actions row --}}
                                <div class="flex flex-wrap items-center gap-4 text-sm mt-auto">
                                    
                                    {{-- Quantity pill --}}
                                    <div class="flex items-center border border-gray-300 rounded-full bg-gray-50 overflow-hidden shadow-sm">
                                        <form action="{{ route('cart.decrement', $item['id']) }}" method="POST" class="border-r border-gray-300 hover:bg-gray-200 transition">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 flex items-center justify-center text-gray-600">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                            </button>
                                        </form>
                                        <div class="px-3 font-semibold text-gray-800">{{ $item['quantity'] }}</div>
                                        <form action="{{ route('cart.add', $item['id']) }}" method="POST" class="border-l border-gray-300 hover:bg-gray-200 transition">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 flex items-center justify-center text-gray-600">
                                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                            </button>
                                        </form>
                                    </div>

                                    <div class="text-gray-300">|</div>

                                    <form action="{{ route('cart.remove', $item['id']) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-teal-700 hover:underline">Delete</button>
                                    </form>

                                    <div class="text-gray-300">|</div>

                                    <a href="#" class="text-teal-700 hover:underline">Save for later</a>
                                    
                                    <div class="text-gray-300">|</div>

                                    <a href="#" class="text-teal-700 hover:underline">Share</a>
                                </div>
                            </div>

                            {{-- Price --}}
                            <div class="sm:w-1/4 md:w-1/5 text-right mt-2 sm:mt-0">
                                @php
                                    $effectivePrice = ($model && $model->discount_price)
                                        ? (float) $model->discount_price
                                        : (float) $item['price'];
                                    $lineTotal = $effectivePrice * $item['quantity'];
                                @endphp

                                @if($model && $model->discount_price)
                                    <div class="text-xs text-gray-400 line-through">₹{{ number_format($item['price'], 2) }}</div>
                                    <div class="font-bold text-lg text-green-600">₹{{ number_format($model->discount_price, 2) }}</div>
                                    <div class="text-xs text-green-700 font-medium">
                                        {{ round((1 - $model->discount_price / $item['price']) * 100) }}% off
                                    </div>
                                @else
                                    <span class="font-bold text-lg text-gray-900">₹{{ number_format($item['price'], 2) }}</span>
                                @endif

                                @if($item['quantity'] > 1)
                                    <div class="text-xs text-gray-500 mt-1">× {{ $item['quantity'] }} = ₹{{ number_format($lineTotal, 2) }}</div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="flex justify-end pt-4 mt-2">
                        <p class="text-lg">
                            Subtotal ({{ count($cart) }} item{{ count($cart) > 1 ? 's' : '' }}):
                            <span class="font-bold text-gray-900">₹{{ number_format($total, 2) }}</span>
                        </p>
                    </div>

                    <div class="flex justify-end pt-2">
                        <form action="{{ route('cart.clear') }}" method="POST">
                            @csrf
                            <button type="submit" onclick="return confirm('Clear entire cart?')" class="text-red-500 hover:underline text-sm">Clear Cart</button>
                        </form>
                    </div>
                @endempty
            </div>
            
            <div class="text-xs text-gray-500 mb-6">
                The price and availability of items at the demo store are subject to change. The shopping cart is a temporary place to store a list of your items and reflects each item's most recent price.
            </div>
        </div>

        {{-- RIGHT COLUMN: CHECKOUT --}}
        @if(!empty($cart))
            <div class="w-full lg:w-1/4">
                <div class="bg-white p-6 shadow-sm mb-4">
                    
                    {{-- Free Delivery Promo --}}
                    <div class="mb-4">
                        <div class="flex items-center gap-2 text-green-700 mb-1">
                            <svg class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" /></svg>
                            <span class="text-sm font-bold">Your order is eligible for FREE Delivery.</span>
                        </div>
                        <p class="text-xs text-gray-600 pl-7">Choose <span class="text-teal-700 hover:underline cursor-pointer">FREE Delivery</span> option at checkout.</p>
                    </div>

                    <p class="text-lg mb-2">
                        Subtotal ({{ count($cart) }} item{{ count($cart) > 1 ? 's' : '' }}):
                        <span class="font-bold text-gray-900">₹{{ number_format($total, 2) }}</span>
                    </p>
                    
                    <div class="flex items-center text-sm text-gray-700 mb-4">
                        <input type="checkbox" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span>This order contains a gift</span>
                    </div>

                    <a href="{{ route('orders.create') }}"
                       class="block w-full text-center bg-yellow-400 hover:bg-yellow-500 shadow-sm border border-yellow-500 rounded-full py-2 px-4 text-sm text-gray-900 transition mb-4">
                        Proceed to Buy
                    </a>

                    <div class="border border-gray-200 rounded p-2 text-sm text-gray-800 flex justify-between items-center bg-gray-50 cursor-pointer shadow-sm">
                        <span>EMI Available</span>
                        <svg class="h-4 w-4 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
