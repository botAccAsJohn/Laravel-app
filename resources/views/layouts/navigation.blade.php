<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @if (auth()->user()->role === 'admin')
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('common.admin_panel') }}
                    </x-nav-link>
                    @endif

                    <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                        {{ __('common.products') }}
                    </x-nav-link>
                    <x-nav-link :href="route('cart.index')" :active="request()->routeIs('cart.*')" class="relative">
                        {{ __('common.cart') }}
                        @php
                        $cart = app(\App\Services\CartService::class)->get(auth()->id());
                        $cartCount = is_array($cart) ? count($cart) - 1 : 0;
                        if ($cartCount < 0) {
                            $cartCount=0;
                            }
                            @endphp
                            <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800" title="{{ trans_choice('common.cart_items_count', $cartCount, ['count' => $cartCount]) }}">
                            {{ $cartCount }}
                            </span>
                    </x-nav-link>
                    <x-nav-link :href="route('recently.index')" :active="request()->routeIs('recently.*')">
                        {{ __('common.recently_viewed') }}
                    </x-nav-link>
                    @if (auth()->user()->role === 'admin')
                    <x-nav-link :href="route('logs.index')" :active="request()->routeIs('logs.*')">
                        {{ __('common.logs') }}
                    </x-nav-link>
                    @endif
                    <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                        {{ __('common.orders') }}
                    </x-nav-link>
                </div>
            </div>

            <!-- Language Switcher -->
            <div class="hidden sm:flex sm:items-center sm:ms-3">
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
                        {{-- English --}}
                        <form action="{{ route('locale.switch') }}" method="POST" class="block w-full">
                            @csrf
                            <input type="hidden" name="lang" value="en">
                            <button type="submit" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">
                                🇺🇸 English
                            </button>
                        </form>

                        {{-- Hindi --}}
                        <form action="{{ route('locale.switch') }}" method="POST" class="block w-full border-t border-gray-100">
                            @csrf
                            <input type="hidden" name="lang" value="hi">
                            <button type="submit" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">
                                🇮🇳 हिन्दी (Hindi)
                            </button>
                        </form>

                        {{-- Arabic (RTL) --}}
                        <form action="{{ route('locale.switch') }}" method="POST" class="block w-full border-t border-gray-100">
                            @csrf
                            <input type="hidden" name="lang" value="ar">
                            <button type="submit" class="w-full text-start px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 transition duration-150 ease-in-out">
                                🇸🇦 العربية (Arabic - RTL)
                            </button>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-4">
                @if (auth()->user()->role === 'admin')
                <a href="{{ route('products.export') }}"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none transition ease-in-out duration-150">
                    {{ __('common.export_products') }}
                </a>
                @endif

                <!-- Notifications Dropdown -->
                <div class="relative ms-3">
                    <x-dropdown align="right" width="w-80">
                        <x-slot name="trigger">
                            <button class="relative inline-flex items-center p-2 text-gray-500 hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>

                                @php
                                $unreadCount = \Illuminate\Support\Facades\Cache::remember('unread_count_' . auth()->id(), 60, function () {
                                return auth()->user()->unreadNotifications()->count();
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
                            </div>

                            @php
                            $latestNotifications = auth()->user()->notifications()->latest()->limit(10)->get();
                            @endphp

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

                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button
                            class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col items-start mr-2">
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">{{ __('Welcome, :name', ['name' => $user->name]) }}</span>
                                <div class="text-slate-900 font-black">{{ $user->name }}</div>
                            </div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('common.profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')" onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('common.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open"
                    class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex"
                            stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round"
                            stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            @if (auth()->user()->role === 'admin')
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('common.admin_panel') }}
            </x-responsive-nav-link>
            @endif
            <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')">
                {{ __('common.products') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.index')">
                {{ __('Notifications') }}
                @php
                $unreadCount = \Illuminate\Support\Facades\Cache::remember('unread_count_' . auth()->id(), 60, function () {
                return auth()->user()->unreadNotifications()->count();
                });
                @endphp
                @if ($unreadCount > 0)
                <span class="ms-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                    {{ $unreadCount }}
                </span>
                @endif
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ $user->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ $user->email }}</div>
            </div>

            <div class="mt-3 space-y-1">

                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('common.profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')" onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('common.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>