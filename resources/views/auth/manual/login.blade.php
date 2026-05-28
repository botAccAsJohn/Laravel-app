<x-guest-layout>
    @section('title', 'Sign In — Manual Auth — ' . config('app.name'))

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 px-4">
        <div class="w-full max-w-md">

            {{-- ── Card ────────────────────────────────────────────────────── --}}
            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl p-8">

                {{-- Logo / Title --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-violet-500 to-fuchsia-500 shadow-lg mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-white">Welcome back</h1>
                    <p class="text-sm text-white/50 mt-1">Manual Auth Flow — Exercise 49.3</p>
                </div>

                {{-- Flash error --}}
                @if ($errors->any())
                <div class="mb-5 flex items-start gap-3 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-xl p-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
                @endif

                {{-- Login Form --}}
                <form id="manual-login-form" method="POST" action="{{ route('manual-auth.login.store') }}" class="space-y-5">
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label for="manual_email" class="block text-xs font-semibold text-white/60 uppercase tracking-widest mb-2">
                            Email address
                        </label>
                        <input id="manual_email" type="email" name="email"
                               value="{{ old('email') }}" required autofocus
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                               placeholder="you@example.com">
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="manual_password" class="block text-xs font-semibold text-white/60 uppercase tracking-widest mb-2">
                            Password
                        </label>
                        <div class="relative">
                            <input id="manual_password" type="password" name="password"
                                   required autocomplete="current-password"
                                   class="w-full bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent transition"
                                   placeholder="••••••••">
                            <button type="button" onclick="togglePwd('manual_password')"
                                    class="absolute inset-y-0 right-3 flex items-center text-white/30 hover:text-white/60 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Remember me --}}
                    <div class="flex items-center gap-3">
                        <input id="manual_remember" type="checkbox" name="remember"
                               class="w-4 h-4 rounded border-white/20 bg-white/5 text-violet-500 focus:ring-violet-500">
                        <label for="manual_remember" class="text-sm text-white/50">Keep me signed in</label>
                    </div>

                    {{-- Submit --}}
                    <button id="manual-login-btn" type="submit"
                            class="w-full py-3 px-6 rounded-xl font-semibold text-sm text-white
                                   bg-gradient-to-r from-violet-600 to-fuchsia-600
                                   hover:from-violet-500 hover:to-fuchsia-500
                                   shadow-lg shadow-violet-500/25 hover:shadow-violet-500/40
                                   transition-all duration-200 active:scale-[0.98]">
                        Sign in
                    </button>
                </form>

                {{-- Magic link request --}}
                <div class="mt-6 pt-6 border-t border-white/10">
                    <p class="text-center text-xs text-white/40 mb-4">Or sign in with a magic link</p>
                    <form method="POST" action="{{ route('manual-auth.magic.generate') }}" class="flex gap-2">
                        @csrf
                        <input type="email" name="email" placeholder="your@email.com"
                               class="flex-1 bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-2.5 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-violet-500 transition">
                        <button type="submit"
                                class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/20 text-white text-sm font-medium transition">
                            Send
                        </button>
                    </form>
                    @if(session('magic_link'))
                    <div class="mt-3 p-3 bg-emerald-500/10 border border-emerald-500/30 rounded-xl">
                        <p class="text-xs text-emerald-400 font-medium mb-1">Magic link (demo — would be emailed):</p>
                        <a href="{{ session('magic_link') }}" class="text-xs text-emerald-300 break-all hover:underline">
                            {{ session('magic_link') }}
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Footer links --}}
                <p class="text-center text-sm text-white/40 mt-6">
                    No account?
                    <a href="{{ route('manual-auth.register') }}" class="text-violet-400 hover:text-violet-300 font-medium transition">
                        Register here
                    </a>
                </p>
            </div>

            {{-- Auth method badge --}}
            <p class="text-center text-xs text-white/20 mt-4">
                Uses <code class="text-violet-400">Auth::attempt()</code> + <code class="text-violet-400">session()->regenerate()</code>
            </p>
        </div>
    </div>

    @push('scripts')
    <script>
        function togglePwd(id) {
            const el = document.getElementById(id);
            el.type = el.type === 'password' ? 'text' : 'password';
        }
    </script>
    @endpush
</x-guest-layout>
