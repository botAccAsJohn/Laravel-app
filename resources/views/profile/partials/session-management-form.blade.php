{{--
    Exercise 49.5 — Remember Me & Session Lifetime
    Profile partial: session management panel.

    Shows:
    • Current session / cookie lifetime settings (read-only info)
    • Remember-me cookie status
    • "Log out all other devices" form using Auth::logoutOtherDevices()
    • Security trade-off documentation in-page
--}}
<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
            {{ __('Browser Sessions & Remember Me') }}
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Manage and log out your active sessions on other browsers or devices.
            If you suspect your account has been compromised, log out all other devices and change your password.
        </p>
    </header>

    {{-- ── Session info strip ───────────────────────────────────────────── --}}
    <div class="mt-5 grid grid-cols-1 sm:grid-cols-3 gap-3 text-sm">
        {{-- Session lifetime --}}
        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Session lifetime</p>
            <p class="font-semibold text-slate-700">
                {{ config('session.lifetime') }} min
                <span class="text-slate-400 font-normal">({{ config('session.lifetime') / 60 }} h idle timeout)</span>
            </p>
            <p class="text-xs text-slate-400 mt-1">Set via <code class="bg-slate-100 px-1 rounded">SESSION_LIFETIME</code> in <code class="bg-slate-100 px-1 rounded">.env</code></p>
        </div>

        {{-- Remember-me cookie --}}
        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Remember-me cookie</p>
            @if(cookie('remember_web_' . sha1(\Illuminate\Support\Facades\Session::getName())) || request()->hasCookie('remember_web'))
                <p class="font-semibold text-emerald-600 flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 inline-block"></span> Active
                </p>
                <p class="text-xs text-slate-400 mt-1">Valid for 5 years from login (Laravel default)</p>
            @else
                <p class="font-semibold text-slate-500">Not set</p>
                <p class="text-xs text-slate-400 mt-1">Tick "Remember me" at next login</p>
            @endif
        </div>

        {{-- Session driver --}}
        <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-3">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-1">Session driver</p>
            <p class="font-semibold text-slate-700 capitalize">{{ config('session.driver') }}</p>
            <p class="text-xs text-slate-400 mt-1">
                @if(config('session.driver') === 'database')
                    Stored in <code class="bg-slate-100 px-1 rounded">sessions</code> table — per-row invalidation works
                @elseif(config('session.driver') === 'redis')
                    Stored in Redis — fast, supports per-key deletion
                @else
                    File-based — logoutOtherDevices deletes matching files
                @endif
            </p>
        </div>
    </div>

    {{-- ── Success flash ────────────────────────────────────────────────── --}}
    @if (session('status') === 'devices-cleared')
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
             x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="mt-4 flex items-center gap-2 p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-emerald-500 flex-shrink-0" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            All other sessions have been invalidated. Remember-me cookies on other devices are revoked.
        </div>
    @endif

    {{-- ── Log out other devices form ───────────────────────────────────── --}}
    <form id="logout-other-devices-form" method="POST"
          action="{{ route('profile.logout-other-devices') }}"
          class="mt-6 space-y-4"
          onsubmit="return confirm('This will sign out all other browsers and revoke all remember-me cookies. Continue?')">
        @csrf
        @method('DELETE')

        <div>
            <x-input-label for="logout_other_devices_password" value="{{ __('Current Password') }}" />
            <x-text-input id="logout_other_devices_password"
                          name="password"
                          type="password"
                          class="mt-1 block w-full"
                          autocomplete="current-password"
                          placeholder="Required to protect you from unauthorized logouts" />
            {{-- Uses the 'logoutOtherDevices' error bag set in ProfileController --}}
            <x-input-error :messages="$errors->logoutOtherDevices->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold
                           bg-red-600 hover:bg-red-700 text-white shadow-sm hover:shadow-md
                           transition-all duration-200 active:scale-[0.98]">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                {{ __('Log Out Other Devices') }}
            </button>
        </div>
    </form>

    {{-- ── Security trade-off documentation ───────────────────────────── --}}
    <div class="mt-6 rounded-xl bg-amber-50 border border-amber-200 p-4 text-sm text-amber-900" x-data="{ open: false }">
        <button @click="open = !open"
                class="flex items-center gap-2 font-semibold text-amber-800 w-full text-left">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            Security trade-off: Session cookie vs. Remember-me cookie
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 ml-auto transition-transform duration-200" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
        <div x-show="open" x-transition class="mt-3 space-y-3 text-amber-800 leading-relaxed">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="bg-white/60 rounded-lg p-3 border border-amber-100">
                    <p class="font-bold text-amber-900 mb-1">🍪 Session cookie</p>
                    <ul class="text-xs space-y-1 list-disc list-inside text-amber-700">
                        <li>Expires when the browser closes (or after <strong>{{ config('session.lifetime') }} min</strong> of inactivity)</li>
                        <li>Low exposure window — cleared automatically</li>
                        <li>Requires re-login every browser restart</li>
                    </ul>
                </div>
                <div class="bg-white/60 rounded-lg p-3 border border-amber-100">
                    <p class="font-bold text-amber-900 mb-1">🔑 Remember-me cookie</p>
                    <ul class="text-xs space-y-1 list-disc list-inside text-amber-700">
                        <li>Survives browser restarts — valid for <strong>5 years</strong> by default</li>
                        <li>Backed by <code class="bg-amber-100 px-1 rounded font-mono">remember_token</code> in the DB</li>
                        <li>Higher exposure: stolen device = full account access</li>
                        <li>Revokable instantly via <strong>"Log Out Other Devices"</strong></li>
                    </ul>
                </div>
            </div>
            <p class="text-xs text-amber-700 border-t border-amber-200 pt-2">
                <strong>Recommendation:</strong> Only tick "Remember me" on personal devices you control.
                On shared or public machines, use session-only login and log out explicitly when done.
            </p>
        </div>
    </div>
</section>
