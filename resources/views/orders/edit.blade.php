@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Edit Order</h1>
        <a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-gray-900">Back to List</a>
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
        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];
        $paymentMethods = ['card', 'upi', 'wallet', 'cod', 'emi', 'netbanking'];
    @endphp

    <form action="{{ route('orders.update', $order->id) }}" method="POST" class="bg-white shadow-md rounded-lg p-6 space-y-4">
        @csrf
        @method('PUT')

        <div class="rounded-lg bg-gray-50 border p-4">
            <p class="text-sm text-gray-500">Customer</p>
            <p class="font-semibold text-gray-800 mt-1">{{ $order->user->name ?? 'Customer not available' }}</p>
            <p class="text-sm text-gray-500">{{ $order->user->email ?? 'Email not available' }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-gray-700 font-bold mb-1">Status <span class="text-red-500">*</span></label>
                <select name="status" class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                    @foreach($statuses as $status)
                        <option value="{{ $status }}" {{ old('status', $order->status) === $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-bold mb-1">Payment Method</label>
                <div class="w-full px-3 py-2 border rounded-lg bg-gray-100 text-gray-600">
                    {{ strtoupper($order->payment_method) }}
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg bg-gray-50 border p-4">
                <p class="text-sm text-gray-500">Address</p>
                <p class="text-gray-800 mt-1 whitespace-pre-line">{{ $order->address }}</p>
            </div>

            <div class="rounded-lg bg-gray-50 border p-4 md:col-span-2">
                <p class="text-sm text-gray-500">Phone</p>
                <p class="text-gray-800 mt-1">{{ $order->phone ?? 'N/A' }}</p>
            </div>
        </div>

        @if($order->items && $order->items->count())
        <div class="mt-6 border-t pt-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4">Order Items</h2>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-lg border">
                        <div class="flex-1">
                            <p class="font-medium text-gray-800">{{ $item->product ? $item->product->name : 'Product Default' }}</p>
                            <p class="text-sm text-gray-500">Unit Price: Rs. {{ number_format((float)$item->unit_price, 2) }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-500 mb-1">Quantity</p>
                            <p class="font-semibold text-gray-800">{{ $item->quantity }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="rounded-lg bg-gray-50 border p-4">
                <p class="text-sm text-gray-500">Total Amount</p>
                <p class="text-lg font-semibold text-gray-800 mt-1">Rs. {{ number_format((float) $order->total_amount, 2) }}</p>
            </div>

            <div class="rounded-lg bg-gray-50 border p-4">
                <p class="text-sm text-gray-500">Discount Amount</p>
                <p class="text-lg font-semibold text-green-600 mt-1">Rs. {{ number_format((float) $order->discount_amount, 2) }}</p>
            </div>

            <div class="rounded-lg bg-gray-50 border p-4">
                <p class="text-sm text-gray-500">Final Amount</p>
                <p class="text-lg font-semibold text-indigo-700 mt-1">Rs. {{ number_format((float) $order->final_amount, 2) }}</p>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-yellow-500 text-white font-bold py-2 px-4 rounded hover:bg-yellow-600 transition">
            Update Order
        </button>
    </form>
</div>
@endsection
