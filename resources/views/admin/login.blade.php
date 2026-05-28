@extends('layouts.admin')

@section('title', __('admin.login_title', ['app' => config('app.name')]))

@section('content')
<div class="min-h-screen w-full flex flex-col md:flex-row bg-[#f8fafc] overflow-x-hidden">
    <!-- Left Pane (Visible on larger screens) -->
    <div class="relative hidden md:flex md:w-1/2 bg-gradient-to-br from-[#026cb6] via-[#004f87] to-[#003865] flex-col items-center justify-center text-center p-8 overflow-hidden select-none">
        <!-- Decorative circular shapes/ambient gradients -->
        <div class="absolute -top-32 -left-32 w-[550px] h-[550px] rounded-full bg-white/5 backdrop-blur-sm border border-white/5"></div>
        <div class="absolute top-[20%] -right-24 w-[300px] h-[300px] rounded-full bg-white/5 backdrop-blur-sm border border-white/5"></div>
        <div class="absolute -bottom-48 right-[-10%] w-[650px] h-[650px] rounded-full bg-black/15 backdrop-blur-sm border border-white/5"></div>
        
        <!-- Logo / Intro Content -->
        <div class="relative z-10 flex flex-col items-center max-w-sm">
            <div class="mb-6 inline-flex items-center justify-center w-16 h-16 bg-white/10 backdrop-blur-md rounded-2xl border border-white/20 shadow-lg">
                <svg class="w-8 h-8 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" stroke-linecap="round" stroke-linejoin="round" />
                    <rect x="9" y="3" width="6" height="4" rx="1" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M9 12h.01M9 16h.01" stroke-linecap="round" stroke-linejoin="round" stroke-width="3" />
                    <path d="M12 12h3M12 16h3" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </div>
            
            <h1 class="text-4xl font-extrabold text-white tracking-tight">
                {{ config('app.name', 'WOSS') }}
            </h1>

            <!-- Stats Group -->

        </div>
    </div>
    
    <!-- Right Pane (Login form container) -->
    <div class="flex-1 flex flex-col justify-between p-8 bg-[#f8fafc] min-h-screen">
        <!-- Spacer helper for alignment -->
        <div class="hidden sm:block h-8"></div>
        
        <div class="flex-1 flex items-center justify-center py-8">
            <div class="w-full max-w-md bg-white rounded-[2rem] border border-slate-100 shadow-xl shadow-slate-200/50 p-8 sm:p-10">
                <!-- Welcome Title -->
                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-slate-800">
                        Welcome back
                    </h2>
                    <p class="text-xs font-medium text-slate-500 mt-1">
                        Sign in to your {{ config('app.name', 'QuizPortal') }} account.
                    </p>
                </div>
                
                {{-- Error Bag --}}
                @if ($errors->any())
                    <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-100 text-rose-800 text-sm">
                        <div class="font-bold flex items-center gap-2 mb-1.5 text-rose-900">
                            <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            {{ __('admin.login_failed') }}
                        </div>
                        <ul class="list-disc list-inside space-y-0.5 text-rose-700 text-xs">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <!-- Login Form -->
                <form class="space-y-6" method="POST" action="{{ route('admin.login.store') }}">
                    @csrf
                    
                    {{-- Email --}}
                    <div>
                        <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-2">
                            Email address
                        </label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            autofocus
                            value="{{ old('email') }}"
                            placeholder="you@example.com"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 placeholder-slate-400 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-sm @error('email') border-red-400 @enderror">
                    </div>
                    
                    {{-- Password --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider">
                                Password
                            </label>
                            <a href="#" class="text-xs font-semibold text-sky-600 hover:text-sky-700 transition-colors">
                                Forgot password?
                            </a>
                        </div>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="current-password"
                            required
                            placeholder="Your password"
                            class="w-full px-4 py-3 rounded-xl border border-slate-200 placeholder-slate-400 text-slate-900 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:border-transparent transition-all text-sm">
                    </div>
                    
                    {{-- Remember Me --}}
                    <div class="flex items-center">
                        <input id="remember" name="remember" type="checkbox"
                               class="h-4 w-4 text-sky-600 focus:ring-sky-500 border-slate-300 rounded cursor-pointer">
                        <label for="remember" class="ml-2 text-sm text-slate-600 cursor-pointer select-none">
                            Remember me
                        </label>
                    </div>
                    
                    {{-- Submit Button --}}
                    <div>
                        <button type="submit"
                                class="w-full py-3 px-4 bg-[#0284c7] hover:bg-[#0275b0] text-white font-semibold rounded-xl shadow-lg shadow-sky-500/10 hover:shadow-sky-500/20 active:scale-[0.98] transition-all text-sm">
                            Sign in
                        </button>
                    </div>
                    
                    {{-- Create account --}}
                    <div class="text-center pt-2">
                        <p class="text-xs font-medium text-slate-500">
                            Don't have an account? <a href="#" class="font-semibold text-sky-600 hover:text-sky-700 transition-colors">Create one</a>
                        </p>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="text-center py-4">
            <p class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">
                &copy; {{ date('Y') }} {{ config('app.name', 'QuizPortal') }}. All rights reserved.
            </p>
        </div>
    </div>
</div>
@endsection