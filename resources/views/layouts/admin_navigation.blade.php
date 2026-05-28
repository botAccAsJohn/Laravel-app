<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo / Title -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('admin.dashboard') }}" class="font-black tracking-wider text-amber-500 uppercase text-sm">
                        {{ config('app.name') }} <span class="text-gray-500 text-xs font-normal lowercase">admin</span>
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @can('view-admin-dashboard')
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                        {{ __('common.admin_panel') }}
                    </x-nav-link>
                    @endcan

                    @can('manage-products')
                    <x-nav-link :href="route('products.index')" :active="request()->routeIs('products.*')">
                        {{ __('common.products') }}
                    </x-nav-link>
                    @endcan

                    @can('manage-orders')
                    <x-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.*')">
                        {{ __('common.orders') }}
                    </x-nav-link>
                    @endcan

                    @can('view-analytics')
                    <x-nav-link :href="route('admin.analytics.index')" :active="request()->routeIs('admin.analytics.*')">
                        {{ __('admin.sales_analytics') }}
                    </x-nav-link>
                    @endcan

                    @can('manage-reports')
                    <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">
                        {{ __('admin.reports_manager') }}
                    </x-nav-link>
                    @endcan

                    @can('send-admin-alerts')
                    <x-nav-link :href="route('admin.alerts.index')" :active="request()->routeIs('admin.alerts.*')">
                        Alerts
                    </x-nav-link>
                    @endcan

                    @can('view-logs')
                    <x-nav-link :href="route('logs.index')" :active="request()->routeIs('logs.*')">
                        {{ __('common.logs') }}
                    </x-nav-link>
                    @endcan

                    @can('manage_users')
                    <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                        Users
                    </x-nav-link>
                    @endcan
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
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div class="flex flex-col items-start mr-2">
                                <span class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">Admin Control</span>
                                <div class="text-slate-900 font-black">{{ Auth::guard('admin')->user()->name }}</div>
                            </div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <!-- Authentication -->
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('admin.logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                                {{ __('common.logout') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
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
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-white border-t border-gray-200">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.dashboard')">
                {{ __('common.admin_panel') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('products.index')" :active="request()->routeIs('products.index')">
                {{ __('common.products') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('orders.index')" :active="request()->routeIs('orders.index')">
                {{ __('common.orders') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('logs.index')" :active="request()->routeIs('logs.index')">
                {{ __('common.logs') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::guard('admin')->user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::guard('admin')->user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <!-- Authentication -->
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('admin.logout')" onclick="event.preventDefault(); this.closest('form').submit();">
                        {{ __('common.logout') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
