@extends('layouts.app')

@section('content')
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="flex flex-col gap-4 mb-6 md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">Orders</h1>
                <p class="text-sm text-gray-500 mt-1">
                    Total orders: <strong>{{ $total_orders ?? (($orders ?? collect())->count()) }}</strong>
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('orders.analytics') }}"
                    class="inline-flex items-center justify-center bg-white text-indigo-700 border border-indigo-100 px-4 py-2 rounded-lg hover:bg-indigo-50 transition font-bold shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    View Order Insights
                </a>
                <a href="{{ route('orders.create') }}"
                    class="inline-flex items-center justify-center bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition font-bold shadow-sm">
                    Create New Order
                </a>
            </div>
        </div>

        @php
            $orders = $orders ?? collect();
            $statusClasses = [
                'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-300',
                'confirmed' => 'bg-blue-100 text-blue-700 border-blue-300',
                'processing' => 'bg-indigo-100 text-indigo-700 border-indigo-300',
                'shipped' => 'bg-purple-100 text-purple-700 border-purple-300',
                'delivered' => 'bg-green-100 text-green-700 border-green-300',
                'cancelled' => 'bg-red-100 text-red-700 border-red-300',
                'refunded' => 'bg-gray-100 text-gray-700 border-gray-300',
            ];
        @endphp

        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            @if($orders->count())
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Order</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Payment</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Placed At</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($orders as $order)
                                @php
                                    $status = $order->status ?? 'pending';
                                    $badgeClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-700 border-gray-300';
                                @endphp
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 align-top">
                                        <div class="font-semibold text-gray-800">
                                            #{{ $order->id }}
                                        </div>
                                        <span
                                            class="inline-flex items-center px-2.5 py-1 mt-2 text-xs font-medium border rounded-full {{ $badgeClass }}">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="text-sm font-medium text-gray-800">
                                            {{ $order->user->name ?? 'Customer not available' }}
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            {{ $order->phone ?? 'No phone provided' }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="text-md text-gray-800">{{ strtoupper($order->payment_method ?? 'N/A') }}</div>

                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="text-sm font-semibold text-gray-800">Rs.
                                            {{ number_format((float) ($order->final_amount ?? 0), 2) }}</div>
                                        <div class="text-xs text-gray-500">
                                            Total: Rs. {{ number_format((float) ($order->total_amount ?? 0), 2) }}
                                            @if(($order->discount_amount ?? 0) > 0)
                                                | Discount: Rs. {{ number_format((float) $order->discount_amount, 2) }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 align-top text-sm text-gray-600">
                                        {{ optional($order->placed_at)->format('d M Y, h:i A') ?? 'Not placed yet' }}
                                    </td>
                                    <td class="px-6 py-4 align-top">
                                        <div class="flex justify-end gap-2 text-sm">
                                            <a href="{{ route('orders.show', $order->id) }}"
                                                class="border border-gray-300 text-gray-700 px-3 py-1.5 rounded-lg hover:bg-gray-100 transition whitespace-nowrap">
                                                View
                                            </a>

                                            @if(Auth::user()->role === 'admin')
                                                <a href="{{ route('orders.edit', $order->id) }}"
                                                    class="bg-yellow-500 text-white px-3 py-1.5 rounded-lg hover:bg-yellow-600 transition whitespace-nowrap">
                                                    Edit
                                                </a>
                                                <form action="{{ route('orders.destroy', $order->id) }}" method="POST"
                                                    onsubmit="return confirm('Are you sure you want to delete this order?');" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="bg-red-600 text-white px-3 py-1.5 rounded-lg hover:bg-red-700 transition whitespace-nowrap">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif

                                            @if(Auth::id() === $order->user_id && in_array($status, ['pending', 'confirmed']))
                                                <form action="{{ route('orders.cancel', $order->id) }}" method="POST"
                                                    onsubmit="return confirm('Do you really want to cancel this order? This action cannot be undone.');" class="inline">
                                                    @csrf
                                                    <button type="submit"
                                                        class="border border-red-500 text-red-600 px-3 py-1.5 rounded-lg hover:bg-red-50 transition whitespace-nowrap font-medium">
                                                        Cancel
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="px-6 py-12 text-center">
                    <h2 class="text-xl font-semibold text-gray-700">No orders found</h2>
                    <p class="text-gray-500 mt-2">Create the first order to get started.</p>
                </div>
            @endif
        </div>
    </div>
@endsection