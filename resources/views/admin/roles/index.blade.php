@extends('layouts.admin')

@section('title', 'Roles & Permissions')

@section('content')
<div class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
        <div>
            <p class="text-xs font-black uppercase tracking-widest text-violet-500 mb-1">Access Control</p>
            <h1 class="text-3xl font-black text-gray-900">Roles & Permissions</h1>
            <p class="text-sm text-gray-500 mt-1">Create and manage roles. Assign permissions to each role. Every change is audit-logged.</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl font-bold text-sm transition-all">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
                User Management
            </a>
            <a href="{{ route('admin.roles.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-xl font-bold text-sm transition-all shadow-lg shadow-violet-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Role
            </a>
        </div>
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

    @if(session('error'))
    <div class="mb-6 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-4 text-sm font-semibold">
        <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- Roles Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5 mb-10">
        @forelse($roles as $role)
        @php
            $colors = [
                'admin'    => ['card' => 'border-rose-200 bg-rose-50/30',    'badge' => 'bg-rose-100 text-rose-700',    'dot' => 'bg-rose-500',    'btn' => 'text-rose-600 hover:bg-rose-50'],
                'manager'  => ['card' => 'border-amber-200 bg-amber-50/30',  'badge' => 'bg-amber-100 text-amber-700',  'dot' => 'bg-amber-500',   'btn' => 'text-amber-600 hover:bg-amber-50'],
                'support'  => ['card' => 'border-blue-200 bg-blue-50/30',    'badge' => 'bg-blue-100 text-blue-700',    'dot' => 'bg-blue-500',    'btn' => 'text-blue-600 hover:bg-blue-50'],
                'customer' => ['card' => 'border-gray-200 bg-gray-50/30',    'badge' => 'bg-gray-100 text-gray-600',    'dot' => 'bg-gray-400',    'btn' => 'text-gray-600 hover:bg-gray-50'],
            ];
            $c = $colors[$role->name] ?? ['card' => 'border-violet-200 bg-violet-50/30', 'badge' => 'bg-violet-100 text-violet-700', 'dot' => 'bg-violet-500', 'btn' => 'text-violet-600 hover:bg-violet-50'];
            $isProtected = in_array($role->name, ['admin', 'customer']);
        @endphp
        <div class="bg-white border {{ $c['card'] }} rounded-2xl shadow-sm overflow-hidden flex flex-col">
            {{-- Card Header --}}
            <div class="px-6 py-5 flex items-start justify-between gap-3 border-b border-gray-100">
                <div class="flex items-center gap-3">
                    <span class="inline-block w-3 h-3 rounded-full {{ $c['dot'] }} shrink-0 mt-0.5"></span>
                    <div>
                        <h2 class="font-black text-gray-900 text-lg leading-tight">{{ $role->display_name }}</h2>
                        <code class="text-[10px] font-mono text-gray-400">{{ $role->name }}</code>
                    </div>
                </div>
                @if($isProtected)
                <span class="text-[9px] font-black uppercase tracking-widest px-2 py-1 bg-gray-100 text-gray-500 rounded-full shrink-0">Protected</span>
                @endif
            </div>

            {{-- Stats --}}
            <div class="px-6 py-4 flex items-center gap-6 border-b border-gray-50">
                <div class="text-center">
                    <p class="text-2xl font-black text-gray-900">{{ $role->permissions_count }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Permissions</p>
                </div>
                <div class="w-px h-8 bg-gray-100"></div>
                <div class="text-center">
                    <p class="text-2xl font-black text-gray-900">{{ $role->users_count }}</p>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Users</p>
                </div>
                <div class="flex-1 text-right">
                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[10px] font-black uppercase {{ $c['badge'] }}">
                        {{ $role->name }}
                    </span>
                </div>
            </div>

            {{-- Description --}}
            <div class="px-6 py-4 flex-1">
                <p class="text-sm text-gray-500">{{ $role->description ?? 'No description.' }}</p>
            </div>

            {{-- Actions --}}
            <div class="px-6 py-4 border-t border-gray-50 flex items-center justify-end gap-2">
                <a href="{{ route('admin.roles.edit', $role) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Edit
                </a>
                @unless($isProtected)
                <form method="POST" action="{{ route('admin.roles.destroy', $role) }}"
                      onsubmit="return confirm('Delete role \'{{ addslashes($role->display_name) }}\'?\nThis will remove the role from {{ $role->users_count }} user(s). This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Delete
                    </button>
                </form>
                @endunless
            </div>
        </div>
        @empty
        <div class="col-span-3 py-16 text-center">
            <p class="text-gray-400 font-semibold">No roles found. <a href="{{ route('admin.roles.create') }}" class="text-violet-600 underline">Create the first role.</a></p>
        </div>
        @endforelse
    </div>

    {{-- Permission Reference --}}
    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100">
            <h2 class="font-black text-gray-900">All Permissions</h2>
            <p class="text-xs text-gray-400 mt-0.5">Every capability available in the system, grouped by area</p>
        </div>
        <div class="p-6 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($permissions as $group => $perms)
            <div>
                <p class="text-[10px] font-black uppercase tracking-widest text-gray-400 mb-3">{{ ucfirst($group) }}</p>
                <div class="space-y-2">
                    @foreach($perms as $perm)
                    <div class="flex items-start gap-2 p-2.5 bg-gray-50 rounded-lg border border-gray-100">
                        <svg class="w-3.5 h-3.5 text-violet-400 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="text-xs font-bold text-gray-800">{{ $perm->display_name }}</p>
                            <code class="text-[10px] font-mono text-gray-400">{{ $perm->name }}</code>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
