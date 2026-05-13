<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Send Manual Alert') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if (session('success'))
                        <div class="mb-4 font-medium text-sm text-green-600">
                            {{ session('success') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.alerts.store') }}">
                        @csrf

                        <!-- Recipient Email -->
                        <div>
                            <x-input-label for="email" :value="__('Recipient Email')" />
                            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
                            <x-input-error :messages="$errors->get('email')" class="mt-2" />
                        </div>

                        <!-- Subject -->
                        <div class="mt-4">
                            <x-input-label for="subject" :value="__('Subject')" />
                            <x-text-input id="subject" class="block mt-1 w-full" type="text" name="subject" :value="old('subject')" required />
                            <x-input-error :messages="$errors->get('subject')" class="mt-2" />
                        </div>

                        <!-- Message -->
                        <div class="mt-4">
                            <x-input-label for="message" :value="__('Message')" />
                            <textarea id="message" name="message" rows="5" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>{{ old('message') }}</textarea>
                            <x-input-error :messages="$errors->get('message')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-end mt-4">
                            <x-primary-button>
                                {{ __('Send Alert') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
