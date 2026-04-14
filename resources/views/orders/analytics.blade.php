<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                {{ __('Your Order Insights') }}
            </h2>
            <a href="{{ route('orders.index') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-800 transition-colors">
                &larr; Back to Orders
            </a>
        </div>
    </x-slot>

    <div class="py-12 bg-gray-50/30 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Overview Metrics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center text-center group hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-gray-900">{{ $totalOrders }}</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Total Orders</div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center text-center group hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-gray-900">${{ number_format($totalSpent, 2) }}</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Total Spent</div>
                </div>

                <div class="bg-white p-8 rounded-3xl shadow-sm border border-gray-100 flex flex-col items-center text-center group hover:shadow-md transition-shadow">
                    <div class="w-12 h-12 bg-amber-50 text-amber-600 rounded-2xl flex items-center justify-center mb-4 group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <div class="text-3xl font-black text-gray-900">${{ number_format($averageOrderValue, 2) }}</div>
                    <div class="text-xs font-bold text-gray-400 uppercase tracking-widest mt-1">Avg. Order Value</div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Favorite Products -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Your Favorite Products</h3>
                    @if($favoriteProducts->isNotEmpty())
                        <div class="space-y-6">
                            @foreach($favoriteProducts as $product)
                                <div class="flex items-center gap-6 p-4 rounded-2xl bg-gray-50/50 border border-transparent hover:border-gray-100 hover:bg-white transition-all group">
                                    <div class="w-20 h-20 bg-white rounded-xl overflow-hidden border border-gray-100 shrink-0">
                                        @if($product['image'])
                                            <img src="{{ Storage::url($product['image']) }}" alt="{{ $product['name'] }}" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-300">
                                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-bold text-gray-900 truncate">{{ $product['name'] }}</h4>
                                        <p class="text-sm text-gray-500">Ordered {{ $product['count'] }} times</p>
                                    </div>
                                    <div class="text-right">
                                        <div class="px-3 py-1 bg-indigo-50 text-indigo-600 rounded-full text-xs font-black uppercase tracking-widest">
                                            #{{ $loop->iteration }} Choice
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12 text-gray-400 italic">No products ordered yet</div>
                    @endif
                </div>

                <!-- Order Status Distribution -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                    <h3 class="text-xl font-bold text-gray-900 mb-6">Order Status Summary</h3>
                    <div class="space-y-4">
                        @forelse($statusBreakdown as $status => $count)
                            <div class="flex items-center justify-between p-4 rounded-2xl bg-gray-50/50">
                                <div class="flex items-center gap-3">
                                    <div class="w-2 h-2 rounded-full @if($status === 'completed' || $status === 'delivered') bg-emerald-500 @elseif($status === 'pending') bg-amber-500 @else bg-gray-400 @endif"></div>
                                    <span class="font-bold text-gray-700 capitalize">{{ $status }}</span>
                                </div>
                                <div class="flex items-center gap-4">
                                    <span class="text-lg font-black text-gray-900">{{ $count }}</span>
                                    <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Orders</span>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-12 text-gray-400 italic">No orders recorded</div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
