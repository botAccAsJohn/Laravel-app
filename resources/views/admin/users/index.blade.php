@extends('layouts.admin')

@section('title', 'User Role Management')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <h1 class="text-3xl font-black text-gray-900">User Management</h1>
            <p class="text-sm text-gray-500 mt-1">Assign and revoke roles. Every change is audit-logged.</p>
        </div>
        <a href="{{ route('admin.dashboard') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all">
            ← Back to Dashboard
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-6 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-5 py-4 text-sm font-semibold">
        <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif

    @if(session('magic_link'))
    <div class="mb-6 bg-indigo-50 border border-indigo-200 text-indigo-800 rounded-xl px-5 py-4 text-sm font-medium">
        <p class="font-bold mb-2">Magic link generated for {{ session('magic_link_user') }}:</p>
        <div class="bg-white px-3 py-2 rounded-lg border border-indigo-100 flex items-center justify-between gap-4">
            <code class="text-indigo-600 break-all select-all">{{ session('magic_link') }}</code>
            <a href="{{ session('magic_link') }}" target="_blank" class="shrink-0 px-3 py-1 bg-indigo-600 text-white rounded-md text-xs font-bold hover:bg-indigo-700 transition">Test Link</a>
        </div>
    </div>
    @endif

    {{-- Role legend --}}
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-xs font-black uppercase tracking-widest text-gray-400 mb-4">Available Roles</h2>
        <div class="flex flex-wrap gap-3">
            @foreach($roles as $role)
            <div class="flex items-center gap-2 px-3 py-2 bg-indigo-50 border border-indigo-100 rounded-lg">
                <span class="inline-block w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                <span class="text-sm font-bold text-indigo-800">{{ $role->display_name }}</span>
                <span class="text-xs text-indigo-400">({{ $role->permissions->count() ?? $role->permissions()->count() }} perms)</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- Users table --}}
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-visible">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <h2 class="font-bold text-gray-900">Customers ({{ $users->total() }})</h2>
            <span class="text-xs text-gray-400">Page {{ $users->currentPage() }} / {{ $users->lastPage() }}</span>
        </div>

        <div class="overflow-visible">
            <table class="w-full text-left text-sm">
                <thead class="bg-gray-50 border-b border-gray-100">
                    <tr>
                        <th class="px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">User</th>
                        <th class="px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Current Roles</th>
                        <th class="px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400">Status</th>
                        <th class="px-6 py-3 text-[10px] font-black uppercase tracking-widest text-gray-400 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($users as $user)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="h-9 w-9 rounded-xl bg-indigo-100 text-indigo-700 flex items-center justify-center font-black text-sm uppercase">
                                    {{ substr($user->name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-gray-900">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($user->roles->isEmpty())
                                <span class="text-xs text-gray-400 italic">No roles</span>
                            @else
                                <div class="flex flex-wrap gap-1">
                                    @foreach($user->roles as $role)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase
                                        {{ match($role->name) {
                                            'admin'    => 'bg-rose-100 text-rose-700',
                                            'manager'  => 'bg-amber-100 text-amber-700',
                                            'support'  => 'bg-blue-100 text-blue-700',
                                            default    => 'bg-gray-100 text-gray-600',
                                        } }}">
                                        {{ $role->display_name }}
                                    </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col gap-1">
                                @if($user->trashed())
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-100 text-red-600">Deleted</span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 text-emerald-700">Active</span>
                                @endif
                                {{-- Exercise 52.3: Show forced-reset badge --}}
                                @if($user->force_password_reset)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-amber-100 text-amber-700">
                                        ⚠ Reset Required
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-right relative" x-data="{ open: false }">
                            <button @click="open = !open" @click.away="open = false" class="p-2 text-gray-400 hover:text-gray-600 transition-colors focus:outline-none">
                                <svg class="w-5 h-5 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                </svg>
                            </button>
                            <div x-show="open" style="display: none;"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="transform opacity-0 scale-95"
                                 x-transition:enter-end="transform opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="transform opacity-100 scale-100"
                                 x-transition:leave-end="transform opacity-0 scale-95"
                                 class="absolute right-6 mt-1 w-48 bg-white rounded-xl shadow-lg border border-gray-100 z-50 py-1 text-left">
                                 
                                <a href="{{ route('admin.users.edit-roles', $user) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium">
                                    Edit Roles
                                </a>

                                @unless($user->trashed())
                                <form method="POST" action="{{ route('admin.users.magic-link', $user) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium">
                                        Generate Magic Link
                                    </button>
                                </form>

                                @can('impersonate_users')
                                <form method="POST" action="{{ route('admin.impersonate.start', $user) }}">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 font-medium">
                                        Impersonate
                                    </button>
                                </form>
                                @endcan

                                <form method="POST"
                                      action="{{ route('admin.users.force-reset', $user) }}"
                                      id="force-reset-{{ $user->id }}"
                                      onsubmit="return confirmForceReset(this, '{{ addslashes($user->name) }}')">
                                    @csrf
                                    <input type="hidden" name="reason" id="reason-{{ $user->id }}" value="">
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-sm font-medium {{ $user->force_password_reset ? 'text-gray-400 cursor-not-allowed' : 'text-red-600 hover:bg-red-50' }}"
                                            {{ $user->force_password_reset ? 'disabled title="Already flagged"' : '' }}>
                                        {{ $user->force_password_reset ? '⚠ Flagged for Reset' : '⚠ Force Reset' }}
                                    </button>
                                </form>
                                @endunless
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-400">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-6 py-4 border-t border-gray-100">
            {{ $users->links() }}
        </div>
    </div>
</div>

<script>
function confirmForceReset(form, userName) {
    const reason = prompt(
        `Force reset for "${userName}"\n\nEnter the reason for this mandatory reset (leave blank for default):`,
        'Suspicious activity detected. Administrative security policy requires an immediate password change.'
    );
    if (reason === null) return false; // cancelled
    document.getElementById('reason-' + form.id.replace('force-reset-', '')).value = reason;
    return confirm(`Are you sure you want to force a password reset for "${userName}"?\n\nThis will:\n• Lock their account until reset is done\n• Invalidate all active sessions\n• Send them an email with the reset link`);
}
</script>
@endsection
