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

            <span id="order-status-badge" class="inline-flex items-center px-3 py-1 text-sm font-medium border rounded-full {{ $badgeClass }}">
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
                <div class="mt-2 space-y-1">
                    <p class="text-sm text-gray-600">Subtotal: <span class="font-medium text-gray-800">@currency($order->total_amount ?? 0)</span></p>
                    
                    @if($order->coupon_code)
                        <p class="text-sm text-indigo-600">Coupon: <span class="font-medium">[{{ $order->coupon_code }}]</span></p>
                    @endif

                    <p class="text-sm text-green-600">Total Discount: <span class="font-medium">- @currency($order->discount_amount ?? 0)</span></p>
                    <p class="text-lg font-bold text-gray-800 border-t pt-1 mt-1">Final Amount: <span class="text-green-600">@currency($order->final_amount ?? 0)</span></p>
                </div>
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
                            @if($item->product)
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="w-full h-full object-cover">
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
                            <p class="font-semibold text-gray-800">@currency($item->total_price)</p>
                            @if($item->discount_amount > 0)
                                <p class="text-xs text-green-600">Saved @currency($item->discount_amount)</p>
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

            <div class="flex flex-col items-start gap-1">
                <a href="{{ URL::temporarySignedRoute('invoices.download', now()->addMinutes(10), ['order' => $order->id]) }}"
                   class="bg-indigo-600 text-white py-2.5 px-6 rounded-lg hover:bg-indigo-700 transition font-medium shadow-sm flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Download Invoice
                </a>
                <span class="text-xs text-gray-500 mt-1">Note: This secure link expires in 10 minutes</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

    <script type="module">
        document.addEventListener('DOMContentLoaded', function () {
            setTimeout(() => {
                if (window.Echo) {
                    window.Echo.private('order.{{ $order->id }}')
                        .listen('.status.updated', function (data) {
                            console.log('Order status updated:', data);
                            
                            // 1. Update status badge text and classes
                            const badge = document.getElementById('order-status-badge');
                            if (badge) {
                                badge.textContent = data.label;
                                
                                const statusClasses = {
                                    'pending': 'bg-yellow-100 text-yellow-700 border-yellow-300',
                                    'confirmed': 'bg-blue-100 text-blue-700 border-blue-300',
                                    'processing': 'bg-indigo-100 text-indigo-700 border-indigo-300',
                                    'shipped': 'bg-purple-100 text-purple-700 border-purple-300',
                                    'delivered': 'bg-green-100 text-green-700 border-green-300',
                                    'cancelled': 'bg-red-100 text-red-700 border-red-300',
                                    'refunded': 'bg-gray-100 text-gray-700 border-gray-300'
                                };
                                
                                badge.className = 'transition-all duration-300 inline-flex items-center px-3 py-1 text-sm font-medium border rounded-full ' + 
                                    (statusClasses[data.status] || 'bg-gray-100 text-gray-700 border-gray-300');
                            }
                            
                            // Real-time notification is now handled universally by the toast component
                        });
                }
            }, 500);
        });
    </script>
@endpush
