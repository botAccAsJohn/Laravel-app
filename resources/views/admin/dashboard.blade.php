@extends('layouts.admin')

@section('title', __('admin.command_center'))

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900 leading-tight">
                {{ __('admin.command_center') }}
            </h1>
            <p class="text-sm font-medium text-gray-500">
                {{ __('admin.live_system_active') }} &mdash; {{ now()->format('D, d M Y · H:i') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            {{-- Import Products Link --}}
            @can('import_products')
            <a href="{{ route('admin.import.form') }}" class="flex items-center gap-2 group px-4 py-2.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-700 rounded-xl font-bold text-xs transition-all border border-emerald-100 shadow-sm">
                <svg class="w-4 h-4 text-emerald-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l4-4m0 0l4 4m-4-4v12" />
                </svg>
                Import Products
            </a>
            @endcan

            {{-- Export Products Link --}}
            @can('manage_products')
            <a href="{{ route('admin.products.export') }}" class="flex items-center gap-2 group px-4 py-2.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-xl font-bold text-xs transition-all border border-blue-100 shadow-sm">
                <svg class="w-4 h-4 text-blue-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export Products
            </a>
            @endcan

            {{-- Sales Analytics Link --}}
            @can('view_analytics')
            <a href="{{ route('analytics.index') }}" class="flex items-center gap-2 group px-4 py-2.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 rounded-xl font-bold text-xs transition-all border border-indigo-100 shadow-sm">
                <svg class="w-4 h-4 text-indigo-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
                {{ __('admin.sales_analytics') }}
            </a>
            @endcan

            {{-- Reports Manager --}}
            @can('manage_reports')
            <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-2 group px-4 py-2.5 bg-amber-50 hover:bg-amber-100 text-amber-700 rounded-xl font-bold text-xs transition-all border border-amber-100 shadow-sm">
                <svg class="w-4 h-4 text-amber-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2a4 4 0 10-8 0v2m8-2v2m-4-1h4m1-7V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
                {{ __('admin.reports_manager') }}
            </a>
            @endcan

            {{-- Alerts Link --}}
            @can('send_alerts')
            <a href="{{ route('admin.alerts.index') }}" class="flex items-center gap-2 group px-4 py-2.5 bg-rose-50 hover:bg-rose-100 text-rose-700 rounded-xl font-bold text-xs transition-all border border-rose-100 shadow-sm">
                <svg class="w-4 h-4 text-rose-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Alerts
            </a>
            @endcan

            {{-- Roles & Permissions --}}
            @can('manage_users')
            <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-2 group px-4 py-2.5 bg-violet-50 hover:bg-violet-100 text-violet-700 rounded-xl font-bold text-xs transition-all border border-violet-100 shadow-sm">
                <svg class="w-4 h-4 text-violet-500 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
                Roles & Permissions
            </a>
            @endcan
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

        {{-- SIDE PANEL: CACHE MONITOR --}}
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 bg-gray-50/50 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        {{ __('admin.system_performance') }}
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
                                <span class="text-[10px] font-bold uppercase tracking-tighter text-gray-400">{{ __('admin.hit_rate') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between w-full px-2 text-sm">
                        <div class="text-center">
                            <p class="font-black text-indigo-900">{{ $stats['hit_rate']['hits'] ?? 0 }}</p>
                            <p class="text-[9px] uppercase font-bold text-indigo-400 tracking-widest">{{ __('admin.hits') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-black text-gray-900">{{ $stats['hit_rate']['total'] ?? 0 }}</p>
                            <p class="text-[9px] uppercase font-bold text-gray-400 tracking-widest">{{ __('admin.total') }}</p>
                        </div>
                        <div class="text-center">
                            <p class="font-black text-rose-900">{{ $stats['hit_rate']['misses'] ?? 0 }}</p>
                            <p class="text-[9px] uppercase font-bold text-rose-400 tracking-widest">{{ __('admin.misses') }}</p>
                        </div>
                    </div>

                    @if(!empty($stats['hit_rate']['recent']))
                    <div class="pt-4 border-t border-gray-100 space-y-2">
                        <p class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">{{ __('admin.recent_cache_events') }}</p>
                        <div class="space-y-1.5 max-h-[120px] overflow-y-auto pr-1">
                            @foreach($stats['hit_rate']['recent'] as $event)
                            <div class="flex justify-between items-center text-xs p-2 bg-gray-50/80 rounded-lg border border-gray-100">
                                <div class="truncate mr-2">
                                    <span class="font-mono text-[10px] text-gray-600 truncate block">{{ $event['key'] }}</span>
                                    <span class="text-[8px] text-gray-400">{{ \Carbon\Carbon::parse($event['time'])->isoFormat('LL') }}</span>
                                </div>
                                @if($event['type'] === 'hit')
                                <span class="px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 font-bold uppercase text-[8px] tracking-wider shrink-0">{{ __('admin.hits') }}</span>
                                @else
                                <span class="px-1.5 py-0.5 rounded bg-rose-100 text-rose-700 font-bold uppercase text-[8px] tracking-wider shrink-0">{{ __('admin.misses') }}</span>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-100">
                        <div class="bg-indigo-50/50 p-4 rounded-xl border border-indigo-100/50">
                            <p class="text-[10px] font-bold text-indigo-400 uppercase tracking-widest">{{ __('admin.memory') }}</p>
                            <p class="text-lg font-bold text-indigo-900">{{ $stats['memory_used'] ?? '—' }}</p>
                        </div>
                        <div class="bg-emerald-50/50 p-4 rounded-xl border border-emerald-100/50">
                            <p class="text-[10px] font-bold text-emerald-400 uppercase tracking-widest">{{ __('admin.uptime') }}</p>
                            <p class="text-lg font-bold text-emerald-900">{{ (int)($stats['uptime_days'] ?? 0) }}d</p>
                        </div>
                    </div>

                    @can('manage_products')
                    <div class="pt-4 border-t border-gray-100">
                        <form method="POST" action="{{ route('admin.cache.clear') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center justify-center gap-2 group px-4 py-3 bg-gray-900 hover:bg-black text-white rounded-xl font-bold text-sm transition-all shadow-lg hover:shadow-gray-200">
                                <svg class="w-4 h-4 text-gray-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                {{ __('admin.optimize_purge_cache') }}
                            </button>
                        </form>
                    </div>
                    @endcan
                </div>
            </div>

            {{-- LIVE PRESENCE: ACTIVE BROWSERS --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-5 bg-indigo-50/30 border-b border-gray-100 flex items-center justify-between">
                    <h3 class="font-bold text-gray-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                        {{ __('admin.active_browsers') }}
                    </h3>
                    <span id="active-browsers-count" class="px-2.5 py-0.5 bg-indigo-100 text-indigo-700 text-xs font-black rounded-full">0</span>
                </div>
                <div class="p-4">
                    <ul id="active-browsers-list" 
                        data-admin-id="{{ Auth::guard('admin')->id() }}" 
                        data-trans-active-now="{{ __('admin.active_now') }}" 
                        data-trans-no-users="{{ __('admin.no_users_active') }}"
                        class="space-y-3 max-h-[300px] overflow-y-auto">
                        <li id="no-browsers-msg" class="text-xs text-center py-6 text-gray-400 font-medium italic">
                            {{ __('admin.no_users_active') }}
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- MAIN PANEL: LIVE ORDER FEED & KEYSPACE --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 bg-white border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900">{{ __('admin.live_order_stream') }}</h3>
                        <p class="text-sm font-medium text-gray-500">{{ __('admin.live_order_desc') }}</p>
                    </div>
                    <div class="h-10 w-10 bg-indigo-50 rounded-full flex items-center justify-center text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                </div>

                <div id="orders-feed" data-currency="{{ config('app.currency') }}" class="p-8 space-y-4 min-h-[200px] max-h-[400px] overflow-y-auto">
                    <div id="no-orders-msg" class="flex flex-col items-center justify-center py-10 text-center">
                        <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <p class="text-gray-400 font-bold uppercase tracking-widest text-xs">{{ __('admin.waiting_orders') }}</p>
                    </div>
                </div>
            </div>

            {{-- KEYSPACE TABLE --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 bg-white border-b border-gray-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-xl font-black text-gray-900">{{ __('admin.keyspace_diagnostics') }}</h3>
                        <p class="text-sm font-medium text-gray-500">{{ __('admin.keyspace_desc') }}</p>
                    </div>
                    <span class="px-3 py-1 bg-indigo-100 text-indigo-700 text-xs font-black rounded-full">
                        {{ __('admin.keys_count', ['count' => count($stats['cached_items'] ?? [])]) }}
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
                                        {{ __('admin.warm') }}
                                    </span>
                                    @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-gray-50 text-gray-400 text-[10px] font-black uppercase border border-gray-100">
                                        <span class="h-1.5 w-1.5 rounded-full bg-gray-300"></span>
                                        {{ __('admin.cold') }}
                                    </span>
                                    @endif
                                </td>
                                <td class="px-8 py-4 text-right">
                                    <p class="text-sm font-black text-gray-900">{{ $item['size'] }}</p>
                                    <p class="text-[10px] text-gray-400 font-bold uppercase">{{ isset($item['ttl']) ? $item['ttl'].'s' : __('admin.permanent') }}</p>
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
@endsection

@push('scripts')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<script src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
@vite(['resources/js/broadcast.js', 'resources/js/browsing.js'])
@endpush
