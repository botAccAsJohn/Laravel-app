@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100/50 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-teal-600 via-teal-700 to-emerald-800 px-8 py-10 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0))]"></div>
            <div class="relative z-10">
                <h1 class="text-3xl font-extrabold tracking-tight">Contact Us</h1>
                <p class="mt-2 text-teal-100 text-sm">
                    Have questions or feedback? We would love to hear from you. Get in touch with us using the form below.
                </p>
            </div>
        </div>

        <!-- Form Body -->
        <div class="p-8">
            <!-- Global Messages -->
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->has('contact_error'))
                <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ $errors->first('contact_error') }}</span>
                </div>
            @endif

            <form action="{{ route('contact.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Name & Email Info (Logged-in or Guest) -->
                @auth('web')
                    <div class="p-4 bg-teal-50/50 border border-teal-100 rounded-2xl flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-teal-100 text-teal-700 rounded-xl flex items-center justify-center font-bold">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-teal-500 uppercase tracking-widest">Logged In As</p>
                                <p class="text-sm font-bold text-gray-800">{{ Auth::user()->name }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold bg-teal-100 text-teal-800 px-3 py-1 rounded-full">Auto-filled</span>
                    </div>
                @endauth

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer Name -->
                    <div>
                        <label for="name" class="block text-sm font-bold text-gray-700 mb-1.5">Your Name</label>
                        <input type="text" name="name" id="name"
                            value="{{ old('name', Auth::user()?->name) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('name') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                            placeholder="e.g. John Doe" required>
                        @error('name')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer Email -->
                    <div>
                        <label for="email" class="block text-sm font-bold text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" name="email" id="email"
                            value="{{ old('email', Auth::user()?->email) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('email') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                            placeholder="e.g. john@example.com" required>
                        @error('email')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Message -->
                <div>
                    <label for="message" class="block text-sm font-bold text-gray-700 mb-1.5">Message</label>
                    <textarea name="message" id="message" rows="5"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-teal-500/20 focus:border-teal-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('message') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                        placeholder="Please write your message here..." required>{{ old('message') }}</textarea>
                    @error('message')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <a href="{{ route('products.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
                        Back to Store
                    </a>
                    <button type="submit"
                        class="px-8 py-3.5 bg-teal-600 text-white rounded-xl hover:bg-teal-700 transition font-bold shadow-md shadow-teal-500/10 hover:shadow-teal-500/20">
                        Send Message
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
