<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    {{-- ── Impersonation Banner ───────────────────────────────────────────── --}}
    @if($isImpersonating)
    <div class="bg-amber-100 text-amber-800 text-sm font-semibold px-4 py-2 flex items-center justify-between gap-4 shadow">
        <span class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
            </svg>
            ⚠️ Admin
            <strong>{{ $impersonatingAdmin?->name ?? 'Unknown' }}</strong>
            is viewing as
            <strong>{{ $impersonatedUser?->name ?? 'a customer' }}</strong>
            ({{ $impersonatedUser?->email ?? '' }})
        </span>
        <form method="POST" action="{{ route('admin.impersonate.stop') }}">
            @csrf
            <button type="submit"
                    class="px-3 py-1 rounded-lg bg-amber-800 text-amber-100 text-xs font-bold hover:bg-amber-900 transition">
                ✕ Stop Impersonating
            </button>
        </form>
    </div>
    @endif

    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @foreach($navItems as $item)
                        <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['activePattern'])" class="relative">
                            {{ $item['label'] }}
                            @if(isset($item['badge']) && $item['badge']['show'])
                                <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                                    {{ $item['badge']['count'] }}
                                </span>
                            @endif
                        </x-nav-link>
                    @endforeach

                    @if($canManageAdmin && $adminItems->isNotEmpty())
                        <div class="hidden sm:flex sm:items-center">
                            <x-dropdown align="left" width="48">
                                <x-slot name="trigger">
                                    <button class="inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium leading-5 text-gray-500 hover:text-gray-700 focus:outline-none focus:text-gray-700 transition duration-150 ease-in-out">
                                        <div>Panel</div>
                                        <div class="ms-1">
                                            <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                            </svg>
                                        </div>
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    @foreach($adminItems as $item)
                                        <x-dropdown-link :href="route($item['route'])" :active="request()->routeIs($item['activePattern'])">
                                            {{ $item['label'] }}
                                        </x-dropdown-link>
                                    @endforeach
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Settings / Language Dropdowns -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-4">
                
                <!-- Language Switcher -->
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ strtoupper(App::getLocale()) }}</div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <form action="{{ route('locale.switch') }}" method="POST" class="block w-full">
                            @csrf
                            <input type="hidden" name="lang" value="en">
                            <button type="submit" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">
                                🇺🇸 English
                            </button>
                        </form>
                        <form action="{{ route('locale.switch') }}" method="POST" class="block w-full border-t border-gray-100">
                            @csrf
                            <input type="hidden" name="lang" value="hi">
                            <button type="submit" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">
                                🇮🇳 हिन्दी (Hindi)
                            </button>
                        </form>
                        <form action="{{ route('locale.switch') }}" method="POST" class="block w-full border-t border-gray-100">
                            @csrf
                            <input type="hidden" name="lang" value="ar">
                            <button type="submit" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">
                                🇸🇦 العربية (Arabic - RTL)
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>

                @if($activeUser)
                    <!-- Notifications Dropdown (Web Only) -->
                    @if($activeGuard === 'web')
                        <div class="relative ms-3">
                            <x-dropdown align="right" width="w-80">
                                <x-slot name="trigger">
                                    <button class="relative inline-flex items-center p-2 text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                        </svg>
                                        @php
                                        $unreadCount = \Illuminate\Support\Facades\Cache::remember('unread_count_' . ($activeUser->id ?? 0), 60, function () use ($activeUser) {
                                            return $activeUser->unreadNotifications()->count() ?? 0;
                                        });
                                        @endphp
                                        @if ($unreadCount > 0)
                                        <span class="absolute top-0 right-0 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-red-100 transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                                            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                                        </span>
                                        @endif
                                    </button>
                                </x-slot>
                                <x-slot name="content">
                                    <div class="px-4 py-2 border-b border-gray-100 font-semibold text-sm text-gray-700">
                                        {{ __('Notifications') }}
                                        @php
                                        $latestNotifications = $activeUser ? \Illuminate\Support\Facades\Cache::remember("user:{$activeUser->id}:notifications:latest", now()->addMinutes(5), function () use ($activeUser) {
                                            return $activeUser->notifications()->latest()->limit(10)->get() ?? collect();
                                        }) : collect();
                                        @endphp
                                    </div>
                                    <div class="max-h-96 overflow-y-auto">
                                        @forelse ($latestNotifications as $notification)
                                        <a href="{{ route('notifications.show', $notification->id) }}" class="block px-4 py-3 hover:bg-gray-50 border-b border-gray-50 last:border-0 transition duration-150 ease-in-out">
                                            <div class="flex flex-col">
                                                <span class="text-sm {{ $notification->read_at ? 'text-gray-500' : 'text-gray-800 font-medium' }}">
                                                    {{ $notification->data['message'] ?? 'New Notification' }}
                                                </span>
                                                <span class="text-[10px] text-gray-400 mt-1 uppercase tracking-tighter">
                                                    {{ $notification->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                        </a>
                                        @empty
                                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                                            {{ __('No notifications yet.') }}
                                        </div>
                                        @endforelse
                                    </div>
                                    <a href="{{ route('notifications.index') }}" class="block text-center py-2 text-xs font-bold text-blue-600 hover:text-blue-800 bg-gray-50 hover:bg-gray-100 transition duration-150 ease-in-out uppercase tracking-widest border-t border-gray-100">
                                        {{ __('View All') }}
                                    </a>
                                </x-slot>
                            </x-dropdown>
                        </div>
                    @endif

                    <!-- User / Admin Profile Dropdown -->
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div class="flex flex-col items-start mr-2">
                                    <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                                        @if($activeGuard === 'admin')
                                            Admin Control
                                        @else
                                            {{ __('Welcome, :name', ['name' => explode(' ', $activeUser->name)[0]]) }}
                                        @endif
                                    </span>
                                    <div class="text-slate-900 font-black">{{ $activeUser->name }}</div>
                                </div>
                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            @if($activeGuard === 'web')
                                <x-dropdown-link :href="route('profile.edit')">
                                    {{ __('common.profile') }}
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('devices.index')">
                                    🔑 {{ __('API Tokens') }}
                                </x-dropdown-link>
                            @endif

                            <!-- Authentication -->
                            <form method="POST" action="{{ $activeGuard === 'admin' ? route('admin.logout') : route('logout') }}">
                                @csrf
                                <x-dropdown-link href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                                    {{ __('common.logout') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm text-gray-500 hover:text-gray-700 underline font-medium">Log in</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}" class="ms-4 text-sm text-gray-500 hover:text-gray-700 underline font-medium">Register</a>
                    @endif
                @endif
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @foreach($navItems as $item)
                <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['activePattern'])">
                    {{ $item['label'] }}
                    @if(isset($item['badge']) && $item['badge']['show'])
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800">
                            {{ $item['badge']['count'] }}
                        </span>
                    @endif
                </x-responsive-nav-link>
            @endforeach

            @if($canManageAdmin && $adminItems->isNotEmpty())
                <div class="border-t border-gray-200 pt-2 mt-2">
                    <div class="px-4 py-2 text-xs font-bold text-gray-500 uppercase tracking-widest">
                        Admin Panel
                    </div>
                    @foreach($adminItems as $item)
                        <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['activePattern'])">
                            {{ $item['label'] }}
                        </x-responsive-nav-link>
                    @endforeach
                </div>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        @if($activeUser)
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $activeUser->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $activeUser->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                @if($activeGuard === 'web')
                    <x-responsive-nav-link :href="route('profile.edit')">
                        {{ __('common.profile') }}
                    </x-responsive-nav-link>
                    <x-responsive-nav-link :href="route('devices.index')" :active="request()->routeIs('devices.*')">
                        🔑 {{ __('API Tokens') }}
                    </x-responsive-nav-link>
                @endif

                <!-- Authentication -->
                <form method="POST" action="{{ $activeGuard === 'admin' ? route('admin.logout') : route('logout') }}">
                    @csrf
                    <x-responsive-nav-link href="#" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('common.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="space-y-1">
                <x-responsive-nav-link :href="route('login')">
                    {{ __('Log in') }}
                </x-responsive-nav-link>
                @if (Route::has('register'))
                <x-responsive-nav-link :href="route('register')">
                    {{ __('Register') }}
                </x-responsive-nav-link>
                @endif
            </div>
        </div>
        @endif
    </div>
</nav>