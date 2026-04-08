@extends('layouts.app')

@push('styles')
@vite(['resources/css/app.css', 'resources/js/app.js'])
@endpush

@section('title', 'Cache Monitor — Admin')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

        <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">

            {{-- HEADER --}}
            <div class="flex flex-col gap-4 border-b border-gray-200 px-6 py-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-900">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <path d="M12 6v6l4 2" />
                        </svg>
                    </div>

                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Cache Monitor</h1>
                        <p class="text-sm font-medium uppercase tracking-widest text-gray-500">Real-time Performance</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <span class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Live
                    </span>

                    <a href="{{ route('admin.cache.index') }}"
                        class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 transition hover:bg-gray-100">
                        Refresh
                    </a>
                </div>
            </div>

            {{-- FLASH --}}
            @if (session('success'))
            <div class="mx-6 mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700">
                {{ session('success') }}
            </div>
            @endif

            <div class="space-y-6 px-6 py-6">

                {{-- TOP STATS --}}
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Hit Rate (Today)</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ (float)($stats['hit_rate']['rate'] ?? 0) }}%
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Total Keys</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900">
                            {{ number_format((int)($stats['total_keys'] ?? 0)) }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Memory Used</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ $stats['memory_used'] ?? '—' }}
                        </div>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div class="text-sm font-medium text-gray-500">Uptime</div>
                        <div class="mt-2 text-2xl font-bold text-gray-900">
                            {{ (int)($stats['uptime_days'] ?? 0) }}
                            <span class="ml-1 text-sm font-medium text-gray-500">Days</span>
                        </div>
                    </div>
                </div>

                {{-- MIDDLE --}}
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

                    <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                        <h3 class="text-lg font-semibold text-gray-900">Performance Metrics</h3>

                        @php
                        $rate = (float)($stats['hit_rate']['rate'] ?? 0);
                        $hits = (int)($stats['hit_rate']['hits'] ?? 0);
                        $misses = (int)($stats['hit_rate']['misses'] ?? 0);
                        $total = (int)($stats['hit_rate']['total'] ?? 0);

                        $r = 35;
                        $circ = round(2 * M_PI * $r, 2);
                        $dashArray = round(($rate / 100) * $circ, 2) . ' ' . $circ;
                        @endphp

                        <div class="mt-6 flex flex-col items-center gap-10 md:flex-row">
                            <div class="relative">
                                <svg width="120" height="120" viewBox="0 0 100 100" class="-rotate-90">
                                    <circle cx="50" cy="50" r="{{ $r }}" class="fill-none stroke-gray-200" stroke-width="6" />
                                    <circle
                                        cx="50"
                                        cy="50"
                                        r="{{ $r }}"
                                        class="fill-none stroke-indigo-500"
                                        stroke-dasharray="{{ $dashArray }}"
                                        stroke-linecap="round"
                                        stroke-width="6" />
                                </svg>

                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="text-xl font-bold text-gray-900">{{ $rate }}%</span>
                                </div>
                            </div>

                            <div class="w-full flex-1">
                                <div class="flex justify-between text-sm font-medium text-gray-600">
                                    <span>Hits: {{ number_format($hits) }}</span>
                                    <span>Misses: {{ number_format($misses) }}</span>
                                    <span>Total: {{ number_format($total) }}</span>
                                </div>

                                <div class="mt-3 h-2 rounded-full bg-gray-200">
                                    <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $rate }} %;"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT SIDE --}}
                    <div class="flex flex-col gap-6">
                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900">Cache Actions</h3>

                            <form method="POST" action="{{ route('admin.cache.clear') }}" class="mt-4">
                                @csrf
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800">
                                    Clear Cache
                                </button>
                            </form>
                        </div>

                        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                            <h3 class="text-lg font-semibold text-gray-900">Redis Server</h3>

                            <div class="mt-4 space-y-3 text-sm">
                                <div class="flex items-center justify-between border-b border-gray-100 pb-2">
                                    <span class="text-gray-500">Version</span>
                                    <span class="font-medium text-gray-900">{{ $stats['redis_info']['redis_version'] ?? '—' }}</span>
                                </div>

                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500">Clients</span>
                                    <span class="font-medium text-gray-900">{{ (int)($stats['redis_info']['connected_clients'] ?? 0) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TABLE --}}
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900">Keyspace</h3>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700">
                            {{ count($stats['cached_items'] ?? []) }}
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Key</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Size</th>
                                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">TTL</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach ($stats['cached_items'] ?? [] as $item)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $item['key'] }}</td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $item['exists'] ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                            {{ $item['exists'] ? 'WARM' : 'COLD' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-700">{{ $item['size'] }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-700">
                                        {{ isset($item['ttl']) ? (int)$item['ttl'].'s' : '—' }}
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
@endsection