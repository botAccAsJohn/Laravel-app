<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-bold text-2xl text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600 leading-tight">
                {{ __('Admin Command Center: Sales Analytics') }}
            </h2>
            <div class="hidden sm:flex items-center gap-4">
                {{-- Export Button --}}
                <a href="{{ route('admin.analytics.export') }}" class="flex items-center gap-2 px-4 py-2 bg-white text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 rounded-xl font-bold text-sm transition-all border border-emerald-100 shadow-sm group">
                    <svg class="w-4 h-4 transition-transform group-hover:translate-y-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export to CSV
                </a>

                <div class="flex items-center gap-2 text-sm text-gray-500 bg-white/50 backdrop-blur-sm px-4 py-2 rounded-full border border-indigo-50 shadow-sm">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-indigo-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-indigo-500"></span>
                    </span>
                    Real-time Analytics Engine
                </div>
            </div>
        </div>
    </x-slot>

    @php
        $totalRevenue = (float) $monthlySales->sum('revenue');
        $avgOrderValue = (float) $monthlySales->avg('average');
        $totalOrders = (int) $monthlySales->sum('count');
        $totalCategories = (int) $salesByCategory->count();
    @endphp

    <div class="py-8 bg-gray-50/50 min-h-screen">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-indigo-50 hover:shadow-md transition-shadow">
                    <div class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-1">Total Revenue</div>
                    <div class="text-2xl font-black text-gray-900">${{ number_format($totalRevenue, 2) }}</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-50 hover:shadow-md transition-shadow">
                    <div class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Avg. Order Value</div>
                    <div class="text-2xl font-black text-gray-900">${{ number_format($avgOrderValue, 2) }}</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-amber-50 hover:shadow-md transition-shadow">
                    <div class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">Total Orders</div>
                    <div class="text-2xl font-black text-gray-900">{{ number_format($totalOrders) }}</div>
                </div>
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-purple-50 hover:shadow-md transition-shadow">
                    <div class="text-xs font-bold text-purple-500 uppercase tracking-wider mb-1">Active Categories</div>
                    <div class="text-2xl font-black text-gray-900">{{ $totalCategories }}</div>
                </div>
            </div>

            <!-- Monthly Sales Chart -->
            <div class="bg-white overflow-hidden shadow-sm rounded-3xl border border-gray-100 p-8">
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Revenue Performance</h3>
                        <p class="text-sm text-gray-500">Monthly breakdown of sales and order volume</p>
                    </div>
                </div>
                <div class="relative h-[400px]">
                    {{-- Data safely passed via data attributes to keep JS pure and editor-friendly --}}
                    <canvas id="monthlySalesChart" 
                        data-labels="{{ json_encode($monthlySales->keys()) }}" 
                        data-values="{{ json_encode($monthlySales->pluck('revenue')) }}">
                    </canvas>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Top Products -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-50">
                        <h3 class="text-lg font-bold text-gray-900">Top 10 Products</h3>
                        <p class="text-xs text-gray-500">Based on quantity sold across all orders</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Product</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">Sold</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">Revenue</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($topProducts as $product)
                                <tr class="hover:bg-indigo-50/30 transition-colors">
                                    <td class="px-8 py-4 text-sm font-semibold text-gray-800">{{ $product['name'] }}</td>
                                    <td class="px-8 py-4 text-sm text-right font-medium text-indigo-600 bg-indigo-50/50">{{ number_format($product['quantity']) }}</td>
                                    <td class="px-8 py-4 text-sm text-right font-bold text-gray-900">${{ number_format($product['revenue'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Top Customers -->
                <div class="bg-white rounded-3xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="p-8 border-b border-gray-50">
                        <h3 class="text-lg font-bold text-gray-900">Elite Customers</h3>
                        <p class="text-xs text-gray-500">Top 10 customers by total lifetime spend</p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-100">
                            <thead class="bg-gray-50/50">
                                <tr>
                                    <th class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-widest">Customer</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">Orders</th>
                                    <th class="px-8 py-4 text-right text-xs font-bold text-gray-400 uppercase tracking-widest">Total Spent</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50">
                                @foreach($topCustomers as $customer)
                                <tr class="hover:bg-purple-50/30 transition-colors">
                                    <td class="px-8 py-4">
                                        <div class="text-sm font-bold text-gray-800">{{ $customer['name'] }}</div>
                                        <div class="text-xs text-gray-400">{{ $customer['email'] }}</div>
                                    </td>
                                    <td class="px-8 py-4 text-sm text-right font-medium text-purple-600 bg-purple-50/50">{{ $customer['order_count'] }}</td>
                                    <td class="px-8 py-4 text-sm text-right font-bold text-gray-900">${{ number_format($customer['total_spent'], 2) }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Sales by Category -->
            <div class="bg-white rounded-3xl shadow-sm border border-gray-100 p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-2">Category Distribution</h3>
                <p class="text-sm text-gray-500 mb-8">Revenue and volume breakdown per product category</p>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-12 items-center">
                    <div class="relative h-[300px]">
                        <canvas id="categoryChart" 
                            data-labels="{{ json_encode($salesByCategory->keys()) }}" 
                            data-values="{{ json_encode($salesByCategory->pluck('revenue')) }}">
                        </canvas>
                    </div>
                    <div class="space-y-4">
                        @foreach($salesByCategory->sortByDesc('revenue') as $category => $data)
                        <div class="group flex justify-between items-center p-4 rounded-2xl hover:bg-gray-50 transition-all border border-transparent hover:border-gray-100">
                            <div class="flex items-center gap-4">
                                <div class="w-2 h-8 rounded-full bg-indigo-500 group-hover:scale-y-125 transition-transform"></div>
                                <div>
                                    <span class="block font-bold text-gray-800">{{ $category }}</span>
                                    <span class="text-xs text-gray-400">{{ number_format($data['quantity']) }} items sold</span>
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-black text-gray-900">${{ number_format($data['revenue'], 2) }}</div>
                                <div class="text-[10px] font-bold text-indigo-500 uppercase tracking-widest">
                                    @php
                                        $share = $totalRevenue > 0 ? ($data['revenue'] / $totalRevenue) * 100 : 0;
                                    @endphp
                                    {{ number_format($share, 1) }}% Share
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Shared configuration for a premium feel
            Chart.defaults.font.family = "'Inter', sans-serif";
            Chart.defaults.color = '#94a3b8';

            // Monthly Sales Chart
            const monthlyCanvas = document.getElementById('monthlySalesChart');
            if (monthlyCanvas) {
                // Parse labels and data from attributes to keep the script 100% pure JS
                const labels = JSON.parse(monthlyCanvas.dataset.labels || '[]');
                const values = JSON.parse(monthlyCanvas.dataset.values || '[]');

                const monthlyCtx = monthlyCanvas.getContext('2d');
                const gradient = monthlyCtx.createLinearGradient(0, 0, 0, 400);
                gradient.addColorStop(0, 'rgba(99, 102, 241, 0.2)');
                gradient.addColorStop(1, 'rgba(99, 102, 241, 0)');

                new Chart(monthlyCtx, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Revenue ($)',
                            data: values,
                            borderColor: '#6366f1',
                            borderWidth: 4,
                            pointBackgroundColor: '#fff',
                            pointBorderColor: '#6366f1',
                            pointBorderWidth: 2,
                            pointHoverRadius: 6,
                            backgroundColor: gradient,
                            fill: true,
                            tension: 0.4
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                backgroundColor: '#1e293b',
                                padding: 12,
                                cornerRadius: 12,
                                displayColors: false
                            }
                        },
                        scales: {
                            x: { grid: { display: false } },
                            y: { 
                                beginAtZero: true,
                                grid: { borderDash: [5, 5], color: '#f1f5f9' },
                                ticks: {
                                    callback: value => '$' + value.toLocaleString()
                                }
                            }
                        }
                    }
                });
            }

            // Category Chart
            const categoryCanvas = document.getElementById('categoryChart');
            if (categoryCanvas) {
                const labels = JSON.parse(categoryCanvas.dataset.labels || '[]');
                const values = JSON.parse(categoryCanvas.dataset.values || '[]');

                const categoryCtx = categoryCanvas.getContext('2d');
                new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: labels,
                        datasets: [{
                            data: values,
                            backgroundColor: [
                                '#6366f1',
                                '#10b981',
                                '#f59e0b',
                                '#ef4444',
                                '#8b5cf6',
                                '#ec4899',
                                '#06b6d4'
                            ],
                            hoverOffset: 20,
                            borderWidth: 0
                        }]
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '75%',
                        plugins: {
                            legend: { display: false }
                        }
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
