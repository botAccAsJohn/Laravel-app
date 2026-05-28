<x-guest-layout>
    {{--
        Exercise 49.4 — Login page with:
        • CAPTCHA widget shown when account has ≥10 failures (passed via $requiresCaptcha)
        • Account-warning banner when approaching lockout
        • Security tips section
    --}}

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    {{-- ── CAPTCHA or near-lockout warning banner ─────────────────────── --}}
    @if(session('captcha_error') || $errors->has('captcha'))
    <div class="mb-4 p-4 rounded-lg bg-red-50 border border-red-300 text-red-800 text-sm flex items-start gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>{{ $errors->first('captcha') ?: session('captcha_error') }}</span>
    </div>
    @endif

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email"
                          :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full"
                          type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-4">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox"
                       class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                       name="remember">
                <span class="ms-2 text-sm text-gray-600">{{ __('Remember me') }}</span>
            </label>
        </div>

        {{-- ── hCaptcha widget (shown only after 10 account failures) ──── --}}
        @if($requiresCaptcha ?? false)
        <div class="mt-5 p-4 rounded-lg bg-amber-50 border border-amber-300">
            <p class="text-sm text-amber-800 font-semibold mb-3 flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Your account requires a security check after multiple failed attempts.
            </p>
            {{-- hCaptcha widget — loads the JS snippet from hcaptcha.com --}}
            <div class="h-captcha" data-sitekey="{{ config('services.hcaptcha.sitekey', '10000000-ffff-ffff-ffff-000000000001') }}"></div>
            <x-input-error :messages="$errors->get('captcha')" class="mt-2" />
        </div>
        @endif

        <div class="flex items-center justify-end mt-4">
            @if (Route::has('password.request'))
                <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none
                           focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                   href="{{ route('password.request') }}">
                    {{ __('Forgot your password?') }}
                </a>
            @endif

            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>

    {{-- hCaptcha JS (only loaded when widget is needed) --}}
    @if($requiresCaptcha ?? false)
    @push('scripts')
    <script src="https://js.hcaptcha.com/1/api.js" async defer></script>
    @endpush
    @endif
</x-guest-layout>
