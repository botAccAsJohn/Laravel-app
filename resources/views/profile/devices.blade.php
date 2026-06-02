<x-app-layout>
    @section('title', 'Device Management — ' . config('app.name'))

    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                {{ __('API Tokens & Device Management') }}
            </h2>
            @if($tokens->count() > 0)
            <form method="POST" action="{{ route('devices.revokeAll') }}"
                  onsubmit="return confirm('Revoke ALL tokens? Every device will be signed out.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-sm font-semibold bg-red-600 hover:bg-red-700 text-white transition-all duration-200 shadow-sm hover:shadow-md">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                    Revoke All Tokens
                </button>
            </form>
            @endif
        </div>
    </x-slot>

    @push('styles')
    <style>
        /* ── Token card gradient shimmer on hover ── */
        .token-card {
            transition: box-shadow 0.2s ease, transform 0.2s ease;
        }
        .token-card:hover {
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.12);
            transform: translateY(-2px);
        }

        /* ── Pulsing live dot ── */
        @keyframes pulse-dot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50%       { opacity: 0.6; transform: scale(1.3); }
        }
        .pulse-dot { animation: pulse-dot 2s ease-in-out infinite; }

        /* ── Countdown badge ── */
        .expiry-near { color: #dc2626; font-weight: 600; }
        .expiry-ok   { color: #16a34a; }
        .expiry-none { color: #6b7280; }

        /* ── Empty state illustration bounce ── */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50%       { transform: translateY(-10px); }
        }
        .float-anim { animation: float 3s ease-in-out infinite; }
    </style>
    @endpush

    <div class="py-10">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ── Flash Messages ─────────────────────────────── --}}
            @if(session('success'))
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
                 x-transition:leave="transition ease-in duration-300"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="flex items-center gap-3 p-4 rounded-xl bg-green-50 border border-green-200 text-green-800 shadow-sm">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                          d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                          clip-rule="evenodd"/>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
            @endif

            {{-- ── Explainer card ─────────────────────────────── --}}
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-100 rounded-2xl p-5 flex gap-4 items-start shadow-sm">
                <div class="mt-0.5 flex-shrink-0 w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 18z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-indigo-900 mb-1">What are API Tokens?</h3>
                    <p class="text-sm text-indigo-700 leading-relaxed">
                        These are <strong>Sanctum personal access tokens</strong> — long-lived bearer tokens issued to
                        mobile apps, CLI tools, or third-party integrations so they can call protected
                        <code class="bg-indigo-100 px-1 rounded font-mono text-xs">/api/*</code> endpoints
                        without a browser session. Each token is scoped to your account; revoke any you no longer
                        recognise to keep your account secure.
                    </p>
                </div>
            </div>

            {{-- ── Token count stat bar ───────────────────────── --}}
            <div class="grid grid-cols-3 gap-4">
                @php
                    $total   = $tokens->count();
                    $expired = $tokens->filter(fn($t) => $t->expires_at && $t->expires_at->isPast())->count();
                    $active  = $total - $expired;
                    $recentlyUsed = $tokens->filter(fn($t) => $t->last_used_at && $t->last_used_at->gt(now()->subDays(7)))->count();
                @endphp
                <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm text-center">
                    <div class="text-3xl font-black text-indigo-600">{{ $total }}</div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest mt-1">Total Tokens</div>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm text-center">
                    <div class="text-3xl font-black text-emerald-600">{{ $active }}</div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest mt-1">Active</div>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm text-center">
                    <div class="text-3xl font-black text-amber-500">{{ $recentlyUsed }}</div>
                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-widest mt-1">Used This Week</div>
                </div>
            </div>

            {{-- ── Token list ─────────────────────────────────── --}}
            @if($tokens->isEmpty())
                <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-16 text-center">
                    <div class="float-anim inline-block mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-20 w-20 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-700 mb-2">No API tokens yet</h3>
                    <p class="text-gray-400 text-sm max-w-sm mx-auto">
                        Tokens are created when you log in via the API (e.g., from Postman or a mobile app).
                        They'll appear here so you can manage them.
                    </p>
                    <div class="mt-6 bg-gray-50 rounded-xl p-4 text-left inline-block max-w-sm text-xs font-mono text-gray-600">
                        <span class="text-purple-600">POST</span> /api/auth/login<br>
                        {<br>
                        &nbsp;&nbsp;"email": "you@example.com",<br>
                        &nbsp;&nbsp;"password": "secret",<br>
                        &nbsp;&nbsp;"device_name": "My Laptop"<br>
                        }
                    </div>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($tokens as $token)
                        @php
                            $isExpired   = $token->expires_at && $token->expires_at->isPast();
                            $expiresIn   = $token->expires_at ? $token->expires_at->diffForHumans() : null;
                            $nearExpiry  = $token->expires_at && !$isExpired &&
                                           $token->expires_at->lt(now()->addDays(3));
                        @endphp
                        <div class="token-card bg-white border {{ $isExpired ? 'border-red-100 bg-red-50/30' : 'border-gray-100' }} rounded-2xl shadow-sm overflow-hidden">
                            <div class="flex items-center gap-4 p-5">

                                {{-- Icon / Status indicator --}}
                                <div class="flex-shrink-0 w-12 h-12 rounded-xl flex items-center justify-center
                                    {{ $isExpired ? 'bg-red-100' : 'bg-indigo-50' }}">
                                    @if($isExpired)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                        </svg>
                                    @endif
                                </div>

                                {{-- Token details --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-gray-800 text-sm">{{ $token->name }}</span>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider
                                            {{ $isExpired
                                                ? 'bg-red-100 text-red-700'
                                                : ($nearExpiry ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700') }}">
                                            @if(!$isExpired)
                                                <span class="pulse-dot w-1.5 h-1.5 rounded-full inline-block
                                                    {{ $nearExpiry ? 'bg-amber-500' : 'bg-emerald-500' }}"></span>
                                            @endif
                                            {{ $isExpired ? 'Expired' : 'Active' }}
                                        </span>
                                    </div>

                                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-gray-500">
                                        {{-- Last used --}}
                                        <span class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            <strong class="font-medium text-gray-600">Last used:</strong>&nbsp;
                                            {{ $token->last_used_at
                                                ? $token->last_used_at->diffForHumans() . ' (' . $token->last_used_at->format('M j, Y H:i') . ')'
                                                : 'Never' }}
                                        </span>

                                        {{-- Created --}}
                                        <span class="flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                            <strong class="font-medium text-gray-600">Created:</strong>&nbsp;
                                            {{ $token->created_at->format('M j, Y H:i') }}
                                        </span>

                                        {{-- Expiry --}}
                                        @if($token->expires_at)
                                        <span class="flex items-center gap-1 {{ $isExpired ? 'expiry-near' : ($nearExpiry ? 'expiry-near' : 'expiry-ok') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01"/>
                                            </svg>
                                            <strong class="font-medium">Expires:</strong>&nbsp;
                                            {{ $isExpired ? 'Expired ' : '' }}{{ $expiresIn }}
                                            ({{ $token->expires_at->format('M j, Y') }})
                                        </span>
                                        @else
                                        <span class="expiry-none">No expiry set</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Revoke button --}}
                                <form method="POST" action="{{ route('devices.revoke', $token->id) }}"
                                      onsubmit="return confirm('Revoke token \'{{ addslashes($token->name) }}\'? Any device using it will be signed out.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            title="Revoke this token"
                                            class="flex-shrink-0 inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold
                                                   border border-red-200 text-red-600 bg-red-50 hover:bg-red-600 hover:text-white hover:border-red-600
                                                   transition-all duration-200">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                        Revoke
                                    </button>
                                </form>
                            </div>

                            {{-- Bottom accent stripe --}}
                            <div class="h-0.5 {{ $isExpired ? 'bg-red-200' : ($nearExpiry ? 'bg-amber-300' : 'bg-gradient-to-r from-indigo-400 to-purple-400') }}"></div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- ── Postman quick-start ─────────────────────────── --}}
            <div class="bg-gray-900 rounded-2xl shadow-lg p-6 text-white" x-data="{ tab: 'login' }">
                <h3 class="font-bold text-sm uppercase tracking-widest text-gray-400 mb-4 flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                    Postman Quick-Start
                </h3>

                {{-- Tabs --}}
                <div class="flex gap-2 mb-4 text-xs font-semibold">
                    @foreach(['login' => 'Login', 'use' => 'Use Token', 'logout' => 'Logout', 'tokens' => 'List Tokens'] as $key => $label)
                    <button @click="tab = '{{ $key }}'"
                            :class="tab === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-gray-800 text-gray-400 hover:bg-gray-700'"
                            class="px-3 py-1.5 rounded-lg transition-all duration-150">{{ $label }}</button>
                    @endforeach
                </div>

                <div class="font-mono text-sm text-green-300 bg-gray-800 rounded-xl p-4 leading-relaxed text-xs overflow-x-auto">
                    <div x-show="tab === 'login'">
                        <span class="text-yellow-400">POST</span> {{ url('/api/login') }}<br>
                        <span class="text-gray-500">Content-Type:</span> application/json<br><br>
                        {<br>
                        &nbsp;&nbsp;<span class="text-blue-300">"email"</span>: <span class="text-green-300">"you@example.com"</span>,<br>
                        &nbsp;&nbsp;<span class="text-blue-300">"password"</span>: <span class="text-green-300">"secret"</span>,<br>
                        &nbsp;&nbsp;<span class="text-blue-300">"device_name"</span>: <span class="text-green-300">"Postman / My Laptop"</span><br>
                        }<br><br>
                        <span class="text-gray-500">// Response:</span><br>
                        { <span class="text-blue-300">"token"</span>: <span class="text-green-300">"1|abc…xyz"</span>, <span class="text-blue-300">"user"</span>: {…} }
                    </div>
                    <div x-show="tab === 'use'" x-cloak>
                        <span class="text-yellow-400">GET</span> {{ url('/api/user') }}<br>
                        <span class="text-gray-500">Authorization:</span> <span class="text-orange-300">Bearer</span> <span class="text-green-300">1|abc…xyz</span><br><br>
                        <span class="text-gray-500">// Any protected endpoint works the same way.</span>
                    </div>
                    <div x-show="tab === 'logout'" x-cloak>
                        <span class="text-yellow-400">POST</span> {{ url('/api/logout') }}<br>
                        <span class="text-gray-500">Authorization:</span> <span class="text-orange-300">Bearer</span> <span class="text-green-300">1|abc…xyz</span><br><br>
                        <span class="text-gray-500">// Response:</span><br>
                        { <span class="text-blue-300">"message"</span>: <span class="text-green-300">"Token revoked."</span> }
                    </div>
                    <div x-show="tab === 'tokens'" x-cloak>
                        <span class="text-yellow-400">GET</span> {{ url('/api/tokens') }}<br>
                        <span class="text-gray-500">Authorization:</span> <span class="text-orange-300">Bearer</span> <span class="text-green-300">1|abc…xyz</span><br><br>
                        <span class="text-gray-500">DELETE</span> {{ url('/api/tokens/{id}') }} <span class="text-gray-500">// revoke one</span><br>
                        <span class="text-gray-500">DELETE</span> {{ url('/api/tokens') }}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <span class="text-gray-500">// revoke all</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
