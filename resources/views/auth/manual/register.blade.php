<x-guest-layout>
    @section('title', 'Create Account — Manual Auth — ' . config('app.name'))

    <div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-slate-900 via-purple-950 to-slate-900 px-4">
        <div class="w-full max-w-md">

            <div class="bg-white/5 backdrop-blur-xl border border-white/10 rounded-3xl shadow-2xl p-8">

                {{-- Title --}}
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-500 to-cyan-500 shadow-lg mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-white">Create your account</h1>
                    <p class="text-sm text-white/50 mt-1">Manual Auth Flow — Exercise 49.3</p>
                </div>

                {{-- Validation errors --}}
                @if ($errors->any())
                <div class="mb-5 bg-red-500/10 border border-red-500/30 text-red-300 text-sm rounded-xl p-4 space-y-1">
                    @foreach ($errors->all() as $error)
                    <p class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                        {{ $error }}
                    </p>
                    @endforeach
                </div>
                @endif

                <form id="manual-register-form" method="POST" action="{{ route('manual-auth.register.store') }}" class="space-y-5">
                    @csrf

                    {{-- Name --}}
                    <div>
                        <label for="reg_name" class="block text-xs font-semibold text-white/60 uppercase tracking-widest mb-2">Full name</label>
                        <input id="reg_name" type="text" name="name" value="{{ old('name') }}" required autofocus
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                               placeholder="Jane Doe">
                    </div>

                    {{-- Email --}}
                    <div>
                        <label for="reg_email" class="block text-xs font-semibold text-white/60 uppercase tracking-widest mb-2">Email address</label>
                        <input id="reg_email" type="email" name="email" value="{{ old('email') }}" required
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                               placeholder="jane@example.com">
                    </div>

                    {{-- Password --}}
                    <div>
                        <label for="reg_password" class="block text-xs font-semibold text-white/60 uppercase tracking-widest mb-2">Password</label>
                        <div class="relative">
                            <input id="reg_password" type="password" name="password" required autocomplete="new-password"
                                   class="w-full bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-3 text-sm
                                          focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                                   placeholder="Min. 8 characters">
                            <button type="button" onclick="togglePwd('reg_password')"
                                    class="absolute inset-y-0 right-3 flex items-center text-white/30 hover:text-white/60 transition">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Confirm Password --}}
                    <div>
                        <label for="reg_password_confirmation" class="block text-xs font-semibold text-white/60 uppercase tracking-widest mb-2">Confirm password</label>
                        <input id="reg_password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password"
                               class="w-full bg-white/5 border border-white/10 text-white placeholder-white/30 rounded-xl px-4 py-3 text-sm
                                      focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition"
                               placeholder="Repeat password">
                    </div>

                    {{-- Submit --}}
                    <button id="manual-register-btn" type="submit"
                            class="w-full py-3 px-6 rounded-xl font-semibold text-sm text-white
                                   bg-gradient-to-r from-emerald-600 to-cyan-600
                                   hover:from-emerald-500 hover:to-cyan-500
                                   shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40
                                   transition-all duration-200 active:scale-[0.98]">
                        Create account
                    </button>
                </form>

                <p class="text-center text-sm text-white/40 mt-6">
                    Already have an account?
                    <a href="{{ route('manual-auth.login') }}" class="text-emerald-400 hover:text-emerald-300 font-medium transition">Sign in</a>
                </p>
            </div>

            <p class="text-center text-xs text-white/20 mt-4">
                Uses <code class="text-emerald-400">Auth::login($user)</code> + <code class="text-emerald-400">session()->regenerate()</code>
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
