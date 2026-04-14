<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-bold text-2xl text-gray-800 leading-tight">
                {{ __('Admin Command Center') }}
            </h2>
            <div class="flex items-center gap-6">
                {{-- Sales Analytics Link --}}
                <a href="{{ route('admin.analytics.index') }}" class="flex items-center gap-2 group px-4 py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition-all border border-indigo-100 shadow-sm">
                    <svg class="w-4 h-4 text-indigo-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Sales Analytics
                </a>

                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
                    </span>
                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Live System Active</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                {{-- SIDE PANEL: CACHE MONITOR --}}
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                                System Performance
                            </h3>
                        </div>

                        <div class="p-6 space-y-6">
                            {{-- Hit Rate Circular Stat --}}
                            <div class="flex flex-col items-center">
                                @php
                                $rate = (float)($stats['hit_rate']['rate'] ?? 0);
                                $r = 50;
                                $circ = 2 * M_PI * $r;
                                $dash = ($rate / 100) * $circ;
                                @endphp
                                <div class="relative inline-flex items-center justify-center">
                                    <svg class="w-32 h-32 transform -rotate-90">
                                        <circle cx="64" cy="64" r="{{ $r }}" stroke="currentColor" stroke-width="6" fill="transparent" class="text-gray-100" />
                                        <circle cx="64" cy="64" r="{{ $r }}" stroke="currentColor" stroke-width="6" fill="transparent"
                                            stroke-dasharray="{{ $circ }}"
                                            stroke-dashoffset="{{ $circ - $dash }}"
                                            stroke-linecap="round"
                                            class="text-indigo-600 transition-all duration-1000 ease-out" />
                                    </svg>
                                    <div class="absolute flex flex-col items-center">
                                        <span class="text-2xl font-black text-gray-900">{{ $rate }}%</span>
                                        <span class="text-[10px] font-bold uppercase tracking-tighter text-gray-400">Hit Rate</span>
                                    </div>
                                </div>
                            </div>

                            <div class="flex justify-between w-full px-2 text-sm">
                                <div class="text-center">
                                    <p class="font-black text-indigo-900">{{ $stats['hit_rate']['hits'] ?? 0 }}</p>
                                    <p class="text-[9px] uppercase font-bold text-indigo-400 tracking-widest">Hits</p>
                                </div>
                                <div class="text-center">
                                    <p class="font-black text-gray-900">{{ $stats['hit_rate']['total'] ?? 0 }}</p>
                                    <p class="text-[9px] uppercase font-bold text-gray-400 tracking-widest">Total</p>
                                </div>
                                <div class="text-center">
                                    <p class="font-black text-rose-900">{{ $stats['hit_rate']['misses'] ?? 0 }}</p>
                                    <p class="text-[9px] uppercase font-bold text-rose-400 tracking-widest">Misses</p>
                                </div>
                            </div>

                            @if(!empty($stats['hit_rate']['recent']))
                            <div class="pt-4 border-t border-gray-100 space-y-2">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">Recent Cache Events</p>
                                <div class="space-y-1.5 max-h-[120px] overflow-y-auto pr-1">
                                    @foreach($stats['hit_rate']['recent'] as $event)
                                    <div class="flex justify-between items-center text-xs p-2 bg-gray-50/80 rounded-lg border border-gray-100">
                                        <div class="truncate mr-2">
                                            <span class="font-mono text-[10px] text-gray-600 truncate block">{{ $event['key'] }}</span>
                                            <span class="text-[8px] text-gray-400">{{ \Carbon\Carbon::parse($event['time'])->diffForHumans(null, true, true) }} ago</span>
                                        </div>
                                        @if($event['type'] === 'hit')
                                            <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold uppercase text-[8px] tracking-wider shrink-0">Hit</span>
                                        @else
                                            <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 font-bold uppercase text-[8px] tracking-wider shrink-0">Miss</span>
                                        @endif
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                                <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50">
                                    <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">Memory</p>
                                    <p class="text-lg font-bold text-indigo-900">{{ $stats['memory_used'] ?? '—' }}</p>
                                </div>
                                <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100/50">
                                    <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest">Uptime</p>
                                    <p class="text-lg font-bold text-emerald-900">{{ (int)($stats['uptime_days'] ?? 0) }}d</p>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <form method="POST" action="{{ route('admin.cache.clear') }}">
                                    @csrf
                                    <button type="submit" class="w-full flex items-center justify-center gap-2 group px-4 py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm transition-all shadow-lg hover:shadow-gray-200">
                                        <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        Optimize & Purge Cache
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- LIVE PRESENCE: ACTIVE BROWSERS --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-6 py-5 bg-indigo-50/30 border-b border-gray-100 flex items-center justify-between">
                            <h3 class="font-bold text-gray-900 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                Active Browsers
                            </h3>
                            <span id="active-browsers-count" class="px-2.5 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-black rounded-full">0</span>
                        </div>
                        <div class="p-4">
                            <ul id="active-browsers-list" class="space-y-3 max-h-[300px] overflow-y-auto">
                                <li id="no-browsers-msg" class="text-xs text-center py-6 text-gray-400 font-medium italic">
                                    No other users active
                                </li>
                            </ul>
                        </div>
                    </div>


                    {{-- SALES ANALYTICS QUICK LINK --}}
                    <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl shadow-lg border border-indigo-500 overflow-hidden group">
                        <div class="p-6">
                            <div class="flex items-center gap-4 mb-4">
                                <div class="h-10 w-10 bg-white/20 rounded-xl flex items-center justify-center text-white backdrop-blur-sm">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 class="font-bold text-white text-lg">Sales Intelligence</h3>
                                    <p class="text-indigo-100 text-xs font-medium">Monthly revenue & top sellers</p>
                                </div>
                            </div>
                            <a href="{{ route('admin.analytics.index') }}" class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-white text-indigo-700 rounded-xl font-bold text-sm transition-all hover:bg-indigo-50 shadow-md">
                                View Full Reports
                                <svg class="w-4 h-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- MAIN PANEL: LIVE ORDER FEED & KEYSPACE --}}
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 bg-white border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-gray-900">Live Order Stream</h3>
                                <p class="text-sm font-medium text-gray-500">Tracking transactions across your empire in real-time.</p>
                            </div>
                            <div class="h-10 w-10 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                </svg>
                            </div>
                        </div>

                        <div id="orders-feed" class="p-8 space-y-4 min-h-[200px] max-h-[400px] overflow-y-auto">
                            <div id="no-orders-msg" class="flex flex-col items-center justify-center py-10 text-center">
                                <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                                    <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">Waiting for incoming orders...</p>
                            </div>
                        </div>
                    </div>

                    {{-- KEYSPACE TABLE --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                        <div class="px-8 py-6 bg-white border-b border-gray-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-black text-gray-900">Keyspace Diagnostics</h3>
                                <p class="text-sm font-medium text-gray-500">Live status of tracked application cache objects.</p>
                            </div>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-black rounded-full">
                                {{ count($stats['cached_items'] ?? []) }} Keys
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left">
                                <thead class="bg-gray-50 border-b border-gray-100">
                                    <tr>
                                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Cache Object</th>
                                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                                        <th class="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Size / TTL</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50">
                                    @foreach ($stats['cached_items'] ?? [] as $item)
                                    <tr class="hover:bg-gray-50/50 transition-colors">
                                        <td class="px-8 py-4">
                                            <p class="text-sm font-bold text-gray-900">{{ $item['label'] ?? $item['key'] }}</p>
                                            <code class="text-[10px] text-gray-400 font-mono">{{ $item['key'] }}</code>
                                        </td>
                                        <td class="px-8 py-4">
                                            @if($item['exists'])
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-[10px] font-black uppercase border border-emerald-100">
                                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                                                Warm
                                            </span>
                                            @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 text-gray-400 text-[10px] font-black uppercase border border-gray-100">
                                                <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                                                Cold
                                            </span>
                                            @endif
                                        </td>
                                        <td class="px-8 py-4 text-right">
                                            <p class="text-sm font-black text-gray-900">{{ $item['size'] }}</p>
                                            <p class="text-[10px] text-gray-400 font-bold uppercase">{{ isset($item['ttl']) ? $item['ttl'].'s' : 'Permanent' }}</p>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @if(Auth::user()->role === 'admin')
    @push('scripts')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
    <script type="module">
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                if (window.Echo) {
                    window.Echo.private('admin.orders')
                        .listen('.order.placed', function(data) {
                            console.log('New order received:', data);
                            addOrderToFeed(data);
                        });

                    // Presence Channel Tracking
                    let activeUsers = [];
                    window.Echo.join('store.browsing')
                        .here((users) => {
                            activeUsers = users;
                            updateBrowsersUI();
                        })
                        .joining((user) => {
                            activeUsers.push(user);
                            updateBrowsersUI();
                        })
                        .leaving((user) => {
                            activeUsers = activeUsers.filter(u => u.id !== user.id);
                            updateBrowsersUI();
                        });

                    function updateBrowsersUI() {
                        const countEl = document.getElementById('active-browsers-count');
                        const listEl = document.getElementById('active-browsers-list');
                        const currentUserId = {
                            {
                                auth() - > id()
                            }
                        };

                        if (countEl) countEl.textContent = activeUsers.length;
                        if (!listEl) return;

                        const others = activeUsers.filter(u => u.id !== currentUserId);

                        if (others.length === 0) {
                            listEl.innerHTML = '<li id="no-browsers-msg" class="text-xs text-center py-6 text-gray-400 font-medium italic">No other users active</li>';
                            return;
                        }

                        listEl.innerHTML = others.map(u => `
                            <li class="flex flex-col gap-1 p-3 rounded-xl hover:bg-gray-50 transition-all border border-transparent hover:border-indigo-50 group">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 bg-indigo-50 text-indigo-600 rounded-lg flex items-center justify-center text-[10px] font-bold uppercase transition-transform group-hover:rotate-12">
                                        ${u.name.charAt(0)}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-black text-gray-900 truncate">${u.name}</p>
                                        <div class="flex items-center gap-1.5">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                            <span class="text-[9px] text-emerald-600 font-black uppercase tracking-widest">Active Now</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 pl-11">
                                    <div class="flex items-center gap-1.5 px-2 py-1 bg-gray-100 rounded-md border border-gray-200/50">
                                        <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span class="text-[10px] font-mono text-gray-500 truncate" title="${u.path}">${u.path}</span>
                                    </div>
                                </div>
                            </li>
                        `).join('');
                    }
                }
            }, 500);

            function addOrderToFeed(data) {
                const feed = document.getElementById('orders-feed');
                const emptyMsg = document.getElementById('no-orders-msg');
                if (!feed) return;
                if (emptyMsg) emptyMsg.remove();

                const item = document.createElement('div');
                item.className = 'group bg-white border border-gray-100 p-5 rounded-2xl hover:border-indigo-100 hover:bg-gray-50/50 transition-all duration-500 flex justify-between items-center opacity-0 translate-y-[-20px] shadow-sm hover:shadow-md';
                item.innerHTML = `
                    <div class="flex items-center gap-4">
                        <div class="h-12 w-12 bg-indigo-50 text-indigo-600 rounded-xl flex items-center justify-center font-bold">
                            #${String(data.orderId).slice(-2)}
                        </div>
                        <div>
                            <p class="font-black text-gray-900 group-hover:text-indigo-600 transition-colors">${data.customerName}</p>
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest">${data.itemsCount} Products | Total ₹${data.orderTotal}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-3 py-1 bg-amber-50 text-amber-600 text-[10px] font-black uppercase tracking-widest rounded-full border border-amber-100">Pending</span>
                        <a href="/orders/${data.orderId}" class="p-2 bg-gray-50 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                `;
                feed.prepend(item);
                setTimeout(() => item.classList.remove('opacity-0', 'translate-y-[-20px]'), 50);
            }
        });
    </script>
    @endpush
    @endif
</x-app-layout>