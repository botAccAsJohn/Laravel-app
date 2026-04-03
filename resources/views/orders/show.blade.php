@extends('layouts.app')

@section('content')
<div class="max-w-3xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Order Details</h1>
        <a href="{{ route('orders.index') }}" class="text-gray-600 hover:text-gray-900 border py-2 px-4 rounded">
            Back to List
        </a>
    </div>

    @php
        $statusClasses = [
            'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
            'confirmed' => 'bg-blue-100 text-blue-700 border-blue-300',
            'processing' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
            'shipped' => 'bg-purple-100 text-purple-700 border-purple-300',
            'delivered' => 'bg-green-100 text-green-700 border-green-300',
            'cancelled' => 'bg-red-100 text-red-700 border-red-300',
            'refunded' => 'bg-gray-100 text-gray-700 border-gray-300',
        ];
        $status = $order->status ?? 'pending';
        $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
    @endphp

    <div class="bg-white shadow-md rounded-lg p-6 space-y-6">
        <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Order #{{ $order->id }}</h2>
                <p class="text-sm text-gray-500 mt-1">
                    Placed {{ optional($order->placed_at)->format('d M Y, h:i A') ?? 'Not available' }}
                </p>
            </div>

            <span class="inline-flex items-center px-3 py-1 text-sm font-medium border rounded-full {{ $badgeClass }}">
                {{ ucfirst($status) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Customer</h3>
                <p class="text-gray-700 font-medium">{{ $order->user->name ?? 'Customer not available' }}</p>
                <p class="text-gray-500 text-sm">{{ $order->user->email ?? 'Email not available' }}</p>
                <p class="text-gray-500 text-sm mt-1">{{ $order->phone ?? 'Phone not available' }}</p>
            </div>

            <div class="border rounded-lg p-4">
                <h3 class="text-lg font-semibold text-gray-800 mb-3">Payment</h3>
                <p class="text-gray-700">Method: <span class="font-medium">{{ strtoupper($order->payment_method ?? 'N/A') }}</span></p>
                <p class="text-gray-700 mt-2">Total: <span class="font-medium">Rs. {{ number_format((float) ($order->total_amount ?? 0), 2) }}</span></p>
                <p class="text-gray-700 mt-2">Discount: <span class="font-medium">Rs. {{ number_format((float) ($order->discount_amount ?? 0), 2) }}</span></p>
                <p class="text-gray-700 mt-2">Final: <span class="font-semibold text-green-600">Rs. {{ number_format((float) ($order->final_amount ?? 0), 2) }}</span></p>
            </div>
        </div>

        <div class="border rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Shipping Address</h3>
            <p class="text-gray-700 whitespace-pre-line">{{ $order->address ?? 'Address not available' }}</p>
        </div>

        @if($order->items && $order->items->count())
        <div class="border rounded-lg p-4">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Order Items</h3>
            <div class="space-y-4">
                @foreach($order->items as $item)
                    <div class="flex items-center gap-4 border-b pb-4 last:border-0 last:pb-0">
                        <div class="w-16 h-16 bg-gray-100 flex items-center justify-center rounded">
                            @if($item->product && $item->product->image_path)
                                <img src="{{ asset('storage/' . $item->product->image_path) }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-xs text-gray-400">No Image</span>
                            @endif
                        </div>
                        <div class="flex-1">
                            @if($item->product)
                                <a href="{{ route('products.show', $item->product->slug) }}" class="font-medium text-blue-600 hover:underline">
                                    {{ $item->product->name }}
                                </a>
                            @else
                                <p class="font-medium text-gray-800">Product Default</p>
                            @endif
                            <p class="text-sm text-gray-500">Qty: {{ $item->quantity }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold text-gray-800">Rs. {{ number_format((float)$item->total_price, 2) }}</p>
                            @if($item->discount_amount > 0)
                                <p class="text-xs text-green-600">Saved Rs. {{ number_format((float)$item->discount_amount, 2) }}</p>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

        <div class="flex flex-wrap gap-4 border-t pt-6">
            @if(Auth::user()->role === 'admin')
                <a href="{{ route('orders.edit', $order->id) }}"
                   class="bg-yellow-500 text-white py-2.5 px-6 rounded-lg hover:bg-yellow-600 transition font-medium shadow-sm">
                    Edit Order
                </a>

                <form action="{{ route('orders.destroy', $order->id) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to delete this order? This action is permanent.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="bg-red-600 text-white py-2.5 px-6 rounded-lg hover:bg-red-700 transition font-medium shadow-sm">
                        Delete Order
                    </button>
                </form>
            @endif

            @if(Auth::id() === $order->user_id && in_array($status, ['pending', 'confirmed']))
                <form action="{{ route('orders.cancel', $order->id) }}" method="POST"
                      onsubmit="return confirm('Are you sure you want to cancel this order? This cannot be undone.');">
                    @csrf
                    <button type="submit" class="border-2 border-red-500 text-red-600 py-2.5 px-6 rounded-lg hover:bg-red-50 transition font-bold shadow-sm">
                        Cancel Order
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
