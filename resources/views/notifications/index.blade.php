<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Notifications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($notifications->isEmpty())
                        <p class="text-gray-500 text-center py-4">{{ __('No notifications found.') }}</p>
                    @else
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-medium text-gray-900">{{ __('Your Notifications') }}</h3>
                            @if(auth()->user()->unreadNotifications->count() > 0)
                                <form action="{{ route('notifications.markAllAsRead') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-sm text-blue-600 hover:text-blue-800 font-semibold transition duration-150 ease-in-out">
                                        {{ __('Mark all as read') }}
                                    </button>
                                </form>
                            @endif
                        </div>

                        <div class="space-y-4">
                            @foreach($notifications as $notification)
                                <a href="{{ route('notifications.show', $notification->id) }}" class="flex items-center justify-between p-4 border rounded-lg transition duration-150 ease-in-out hover:shadow-md {{ $notification->read_at ? 'bg-gray-50' : 'bg-white border-blue-200' }}">
                                    <div class="flex-1">
                                        <div class="text-sm font-medium {{ $notification->read_at ? 'text-gray-600' : 'text-gray-900' }}">
                                            {{ $notification->data['message'] ?? 'New Notification' }}
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1">
                                            {{ $notification->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        @if(!$notification->read_at)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ __('New') }}
                                            </span>
                                        @endif
                                        <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </a>
                            @endforeach
                        </div>

                        <div class="mt-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
