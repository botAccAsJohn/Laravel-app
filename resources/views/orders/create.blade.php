@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-800">Checkout</h1>
            <p class="text-sm text-gray-500 mt-1">Review your cart and add the remaining order details.</p>
        </div>
        <a href="{{ route('cart.index') }}" class="text-gray-600 hover:text-gray-900">Back to Cart</a>
    </div>

    @if ($errors->any())
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $paymentMethods = ['card', 'upi', 'wallet', 'cod', 'emi', 'netbanking'];
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form action="{{ route('orders.store') }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-5">
                @csrf

                <div>
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Delivery Details</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-bold mb-1">Address <span class="text-red-500">*</span></label>
                            <textarea name="address" rows="4" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>{{ old('address') }}</textarea>
                        </div>

                        <div>
                            <label class="block text-gray-700 font-bold mb-1">Phone</label>
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                   placeholder="Enter phone number">
                        </div>
                    </div>
                </div>

                <div class="border-t pt-5">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Details</h2>

                    <div>
                        <label class="block text-gray-700 font-bold mb-1">Payment Method <span class="text-red-500">*</span></label>
                        <select name="payment_method" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            <option value="">Select payment method</option>
                            @foreach($paymentMethods as $method)
                                <option value="{{ $method }}" {{ old('payment_method') === $method ? 'selected' : '' }}>
                                    {{ strtoupper($method) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="border-t pt-5">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                        <div class="rounded-lg bg-gray-50 p-4 border">
                            <p class="text-gray-500">Total Amount</p>
                            <p class="text-lg font-semibold text-gray-800 mt-1">Rs. {{ number_format((float) $total_amount, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 border">
                            <p class="text-gray-500">Discount</p>
                            <p class="text-lg font-semibold text-green-600 mt-1">Rs. {{ number_format((float) $discount_amount, 2) }}</p>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 border">
                            <p class="text-gray-500">Final Amount</p>
                            <p class="text-lg font-semibold text-indigo-700 mt-1">Rs. {{ number_format((float) $final_amount, 2) }}</p>
                        </div>
                    </div>
                </div>

                <button type="submit"
                        class="w-full bg-indigo-600 text-white font-bold py-3 px-4 rounded-lg hover:bg-indigo-700 transition">
                    Place Order
                </button>
            </form>
        </div>

        <div>
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Order Summary</h2>

                <div class="space-y-4">
                    @foreach($cart as $item)
                        @php
                            $model = $cartModels[$item['id']] ?? null;
                            $effectivePrice = $model && $model->discount_price ? (float) $model->discount_price : (float) $item['price'];
                            $lineTotal = $effectivePrice * $item['quantity'];
                        @endphp

                        <div class="flex gap-3 border-b pb-4">
                            <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden flex items-center justify-center shrink-0">
                                @if($model && $model->image_path)
                                    <img src="{{ asset('storage/' . $model->image_path) }}" alt="{{ $item['name'] }}" class="w-full h-full object-cover">
                                @else
                                    <span class="text-xs text-gray-400">No image</span>
                                @endif
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="font-medium text-gray-800">{{ $item['name'] }}</p>
                                <p class="text-sm text-gray-500">Qty: {{ $item['quantity'] }}</p>
                                <p class="text-sm font-semibold text-gray-700 mt-1">Rs. {{ number_format($lineTotal, 2) }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-5 space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600">
                        <span>Items Total</span>
                        <span>Rs. {{ number_format((float) $total_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-green-600">
                        <span>Discount</span>
                        <span>- Rs. {{ number_format((float) $discount_amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between text-base font-semibold text-gray-900 border-t pt-3">
                        <span>Order Total</span>
                        <span>Rs. {{ number_format((float) $final_amount, 2) }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
