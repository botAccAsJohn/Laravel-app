<x-app-layout>
    @section('title', 'Customer Impersonation — Admin — ' . config('app.name'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                </svg>
                Customer Impersonation
                <span class="ml-2 text-xs font-bold bg-amber-100 text-amber-700 px-2.5 py-0.5 rounded-full uppercase tracking-wider">
                    Exercise 49.3
                </span>
            </h2>
            <span class="text-sm text-gray-500 font-medium">
                Logged in as: <strong class="text-gray-700">{{ Auth::guard('admin')->user()->name }}</strong>
            </span>
        </div>
    </x-slot>

    @push('styles')
    <style>
        .customer-row { transition: background-color 0.15s ease; }
        .customer-row:hover { background-color: #fefce8; }
        .log-row-active { background-color: #fef9c3; }
        @keyframes pulse-badge {
            0%, 100% { opacity: 1; }
            50%       { opacity: 0.6; }
        }
        .pulse-badge { animation: pulse-badge 2s ease-in-out infinite; }
    </style>
    @endpush

    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            {{-- ── Flash messages ──────────────────────────────────────────── --}}
            @foreach(['success' => 'green', 'error' => 'red'] as $key => $color)
            @if(session($key))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 class="flex items-center gap-3 p-4 rounded-xl bg-{{ $color }}-50 border border-{{ $color }}-200 text-{{ $color }}-800 shadow-sm text-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-{{ $color }}-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="font-medium">{{ session($key) }}</span>
            </div>
            @endif
            @endforeach

            {{-- ── Concept explainer ────────────────────────────────────────── --}}
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 border border-amber-200 rounded-2xl p-5 text-sm text-amber-900">
                <h3 class="font-bold mb-1">How impersonation works here</h3>
                <p class="text-amber-800 leading-relaxed">
                    Clicking <strong>Impersonate</strong> calls
                    <code class="bg-amber-100 px-1 rounded font-mono text-xs">Auth::guard('web')->loginUsingId($id)</code>
                    which logs into the customer's session <em>permanently</em> for this browser tab.
                    Your admin session (<code class="bg-amber-100 px-1 rounded font-mono text-xs">auth:admin</code>) remains intact
                    in another guard — you can still open <strong>/admin/dashboard</strong> in a new tab.
                    Every impersonation start/stop is written to the <code class="bg-amber-100 px-1 rounded font-mono text-xs">impersonation_logs</code> table below.
                </p>
            </div>

            {{-- ── Two-column grid ─────────────────────────────────────────── --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Customer list --}}
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <h3 class="font-bold text-sm text-gray-700 uppercase tracking-widest">Customers</h3>
                        <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-bold px-2 py-0.5 rounded-full">{{ $customers->count() }}</span>
                    </div>

                    @if($customers->isEmpty())
                        <div class="p-10 text-center text-gray-400 text-sm">No customers found.</div>
                    @else
                    <div class="divide-y divide-gray-50">
                        @foreach($customers as $customer)
                        <div class="customer-row flex items-center gap-4 px-5 py-3.5">
                            {{-- Avatar initials --}}
                            <div class="flex-shrink-0 w-9 h-9 rounded-full bg-gradient-to-br from-violet-400 to-fuchsia-400
                                        flex items-center justify-center text-white font-bold text-sm shadow-sm">
                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                            </div>

                            {{-- Info --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-semibold text-gray-800 truncate">{{ $customer->name }}</p>
                                <p class="text-xs text-gray-400 truncate">{{ $customer->email }}</p>
                            </div>

                            {{-- ID badge --}}
                            <span class="text-xs text-gray-400 font-mono">#{{ $customer->id }}</span>

                            {{-- Impersonate button --}}
                            <form method="POST"
                                  action="{{ route('admin.impersonate.start', $customer->id) }}"
                                  onsubmit="return confirm('Impersonate {{ addslashes($customer->name) }}? This will be logged.')">
                                @csrf
                                <button type="submit"
                                        title="Impersonate this customer"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold
                                               border border-amber-200 text-amber-700 bg-amber-50
                                               hover:bg-amber-600 hover:text-white hover:border-amber-600
                                               transition-all duration-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                    </svg>
                                    Impersonate
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>

                {{-- Auth method code reference --}}
                <div class="bg-gray-900 rounded-2xl shadow-lg p-6 text-white self-start" x-data="{ tab: 'attempt' }">
                    <h3 class="font-bold text-xs uppercase tracking-widest text-gray-400 mb-4 flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                        </svg>
                        Auth Method Reference — Exercise 49.3
                    </h3>

                    {{-- Tabs --}}
                    <div class="flex gap-1.5 mb-4 text-xs font-semibold flex-wrap">
                        @foreach(['attempt' => 'Auth::attempt()', 'loginById' => 'loginUsingId()', 'onceById' => 'onceUsingId()', 'logout' => 'logout()'] as $key => $label)
                        <button @click="tab = '{{ $key }}'"
                                :class="tab === '{{ $key }}' ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'"
                                class="px-3 py-1.5 rounded-lg transition-all duration-150 whitespace-nowrap">{{ $label }}</button>
                        @endforeach
                    </div>

                    <div class="font-mono text-xs text-green-300 bg-gray-800 rounded-xl p-4 leading-relaxed overflow-x-auto">

                        <div x-show="tab === 'attempt'">
                            <span class="text-gray-400">// Validates credentials against the DB.</span><br>
                            <span class="text-gray-400">// $remember=true sets a long-lived cookie.</span><br><br>
                            <span class="text-blue-300">Auth</span>::<span class="text-yellow-300">attempt</span>([<br>
                            &nbsp;&nbsp;<span class="text-green-300">'email'</span>    => <span class="text-orange-300">$email</span>,<br>
                            &nbsp;&nbsp;<span class="text-green-300">'password'</span> => <span class="text-orange-300">$password</span>,<br>
                            ], <span class="text-orange-300">$remember</span>);<br><br>
                            <span class="text-gray-400">// CRITICAL after success:</span><br>
                            <span class="text-orange-300">$request</span>-><span class="text-yellow-300">session</span>()-><span class="text-yellow-300">regenerate</span>();
                        </div>

                        <div x-show="tab === 'loginById'" x-cloak>
                            <span class="text-gray-400">// Logs in permanently as user #$id.</span><br>
                            <span class="text-gray-400">// Session persists across requests.</span><br>
                            <span class="text-gray-400">// Used for impersonation.</span><br><br>
                            <span class="text-blue-300">Auth</span>::<span class="text-yellow-300">loginUsingId</span>(<span class="text-orange-300">$userId</span>);<br><br>
                            <span class="text-gray-400">// Or with remember-me:</span><br>
                            <span class="text-blue-300">Auth</span>::<span class="text-yellow-300">loginUsingId</span>(<span class="text-orange-300">$userId</span>, <span class="text-purple-300">true</span>);
                        </div>

                        <div x-show="tab === 'onceById'" x-cloak>
                            <span class="text-gray-400">// Auth for ONE request only.</span><br>
                            <span class="text-gray-400">// No session cookie written.</span><br>
                            <span class="text-gray-400">// Ideal for signed magic links.</span><br><br>
                            <span class="text-orange-300">$user</span> = <span class="text-blue-300">Auth</span>::<span class="text-yellow-300">onceUsingId</span>(<span class="text-orange-300">$userId</span>);<br><br>
                            <span class="text-gray-400">// Then start a real session:</span><br>
                            <span class="text-blue-300">Auth</span>::<span class="text-yellow-300">login</span>(<span class="text-orange-300">$user</span>);<br>
                            <span class="text-orange-300">$request</span>-><span class="text-yellow-300">session</span>()-><span class="text-yellow-300">regenerate</span>();
                        </div>

                        <div x-show="tab === 'logout'" x-cloak>
                            <span class="text-gray-400">// Full three-step logout:</span><br><br>
                            <span class="text-blue-300">Auth</span>::<span class="text-yellow-300">logout</span>();<br><br>
                            <span class="text-gray-400">// Destroy all session data:</span><br>
                            <span class="text-orange-300">$request</span>-><span class="text-yellow-300">session</span>()-><span class="text-yellow-300">invalidate</span>();<br><br>
                            <span class="text-gray-400">// Rotate CSRF token:</span><br>
                            <span class="text-orange-300">$request</span>-><span class="text-yellow-300">session</span>()-><span class="text-yellow-300">regenerateToken</span>();
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Audit Log ────────────────────────────────────────────────── --}}
            <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-gray-100 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <h3 class="font-bold text-sm text-gray-700 uppercase tracking-widest">Impersonation Audit Log</h3>
                    <span class="ml-auto bg-gray-100 text-gray-500 text-xs font-bold px-2 py-0.5 rounded-full">
                        Last {{ $logs->count() }}
                    </span>
                </div>

                @if($logs->isEmpty())
                <div class="p-10 text-center text-gray-400 text-sm">No impersonation activity yet.</div>
                @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-50 text-left">
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Admin</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Target Customer</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Started</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Duration</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
                                <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-50">
                            @foreach($logs as $log)
                            <tr class="{{ $log->isActive() ? 'log-row-active' : '' }}">
                                <td class="px-5 py-3.5 font-mono text-xs text-gray-600">{{ $log->admin_email }}</td>
                                <td class="px-5 py-3.5">
                                    <p class="text-sm font-semibold text-gray-800">{{ $log->targetUser?->name ?? 'Deleted user' }}</p>
                                    <p class="text-xs text-gray-400 font-mono">{{ $log->target_email }}</p>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-500">
                                    <span title="{{ $log->created_at->format('Y-m-d H:i:s') }}">
                                        {{ $log->created_at->diffForHumans() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-xs text-gray-500">
                                    @if($log->stopped_at)
                                        {{ $log->created_at->diffForHumans($log->stopped_at, true) }}
                                    @else
                                        <span class="pulse-badge text-amber-600 font-semibold">Active</span>
                                    @endif
                                </td>
                                <td class="px-5 py-3.5 font-mono text-xs text-gray-400">{{ $log->ip_address ?? '—' }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                        {{ $log->isActive()
                                            ? 'bg-amber-100 text-amber-700'
                                            : 'bg-gray-100 text-gray-500' }}">
                                        {{ $log->isActive() ? 'Active' : 'Ended' }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
