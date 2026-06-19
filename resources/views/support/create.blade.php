@extends('layouts.app')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-12">
    <div class="bg-white rounded-3xl shadow-xl border border-gray-100/50 overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-purple-800 px-8 py-10 text-white relative overflow-hidden">
            <div class="absolute inset-0 bg-grid-white/10 [mask-image:linear-gradient(0deg,white,rgba(255,255,255,0))]"></div>
            <div class="relative z-10">
                <h1 class="text-3xl font-extrabold tracking-tight">Submit a Support Ticket</h1>
                <p class="mt-2 text-indigo-100 text-sm">
                    Have an issue or a question? Fill out the form below and our support team will get back to you shortly.
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

            @if($errors->has('ticket_error'))
                <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-800 rounded-2xl flex items-center gap-3 animate-fade-in">
                    <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span class="text-sm font-semibold">{{ $errors->first('ticket_error') }}</span>
                </div>
            @endif

            <form action="{{ route('support.tickets.store') }}" method="POST" class="space-y-6">
                @csrf

                <!-- Name & Email Info (Logged-in or Guest) -->
                @auth('web')
                    <div class="p-4 bg-indigo-50/50 border border-indigo-100 rounded-2xl flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="h-10 w-10 bg-indigo-100 text-indigo-700 rounded-xl flex items-center justify-center font-bold">
                                {{ substr(Auth::user()->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest">Logged In As</p>
                                <p class="text-sm font-bold text-gray-800">{{ Auth::user()->name }}</p>
                            </div>
                        </div>
                        <span class="text-xs font-semibold bg-indigo-100 text-indigo-800 px-3 py-1 rounded-full">Auto-filled</span>
                    </div>
                @endauth

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Customer Name -->
                    <div>
                        <label for="customer_name" class="block text-sm font-bold text-gray-700 mb-1.5">Your Name</label>
                        <input type="text" name="customer_name" id="customer_name"
                            value="{{ old('customer_name', Auth::user()?->name) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('customer_name') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                            placeholder="e.g. John Doe" required>
                        @error('customer_name')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Customer Email -->
                    <div>
                        <label for="customer_email" class="block text-sm font-bold text-gray-700 mb-1.5">Email Address</label>
                        <input type="email" name="customer_email" id="customer_email"
                            value="{{ old('customer_email', Auth::user()?->email) }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('customer_email') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                            placeholder="e.g. john@example.com" required>
                        @error('customer_email')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Subject -->
                    <div class="md:col-span-2">
                        <label for="subject" class="block text-sm font-bold text-gray-700 mb-1.5">Subject</label>
                        <input type="text" name="subject" id="subject"
                            value="{{ old('subject') }}"
                            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('subject') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                            placeholder="Brief summary of the issue" required>
                        @error('subject')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Priority -->
                    <div>
                        <label for="priority" class="block text-sm font-bold text-gray-700 mb-1.5">Priority</label>
                        <div class="relative">
                            <select name="priority" id="priority"
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-gray-800 appearance-none bg-none @error('priority') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror">
                                <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                                <option value="medium" {{ old('priority') === 'medium' || !old('priority') ? 'selected' : '' }}>Medium</option>
                                <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                                <option value="critical" {{ old('priority') === 'critical' ? 'selected' : '' }}>Critical</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-500">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path d="M9.293 12.95l.707.707L15.657 8l-1.414-1.414L10 10.828 5.757 6.586 4.343 8z"/>
                                </svg>
                            </div>
                        </div>
                        @error('priority')
                            <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-bold text-gray-700 mb-1.5">Description</label>
                    <textarea name="description" id="description" rows="5"
                        class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all font-medium text-gray-800 placeholder-gray-400 @error('description') border-rose-300 focus:ring-rose-500/20 focus:border-rose-500 @enderror"
                        placeholder="Please provide detailed information about your issue..." required>{{ old('description') }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs font-semibold text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center justify-between pt-4 border-t border-gray-100">
                    <a href="{{ route('products.index') }}" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">
                        Back to Store
                    </a>
                    <button type="submit"
                        class="px-8 py-3.5 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition font-bold shadow-md shadow-indigo-500/10 hover:shadow-indigo-500/20">
                        Submit Ticket
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
